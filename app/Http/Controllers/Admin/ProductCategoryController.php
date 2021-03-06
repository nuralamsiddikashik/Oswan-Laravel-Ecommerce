<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductCategoryExport;
use App\Http\Controllers\Controller;
use App\Models\Admin\ProductCategory;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->has('type') && request()->input('type') == 'trash') {
            $productCategories = ProductCategory::onlyTrashed()->orderBy('created_at', 'desc')->paginate(8);
        } elseif (request()->has('type') && request()->input('type') == 'all') {
            $productCategories = ProductCategory::withTrashed()->orderBy('created_at', 'desc')->paginate(8);
        } else {
            $productCategories = ProductCategory::orderBy('created_at', 'desc')->paginate(8);
        }

        return view('admin.product-category.index', compact('productCategories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.product-category.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $productCategory              = new ProductCategory();
        $productCategory->name        = $request->input('name');
        $productCategory->description = $request->input('description');

        // Slug generation
        $uniqueSlug = Str::slug($request->input('name'));
        $next       = 2;
        while (ProductCategory::where('slug', $uniqueSlug)->first()) {
            $uniqueSlug = Str::slug($request->input('name')) . '-' . $next;
            $next++;
        }
        $productCategory->slug = $uniqueSlug;

        // Thumbnail upload
        if ($request->has('thumbnail')) {
            $thumbnail     = $request->file('thumbnail');
            $path          = 'uploads/images/product-categories';
            $thumbnailName = time() . '_' . rand(100, 999) . '_' . $thumbnail->getClientOriginalName();
            $thumbnail->move(public_path($path), $thumbnailName);
            $productCategory->thumbnail = $thumbnailName;
        }

        if ($productCategory->save()) {
            return redirect()->route('admin.product-category.edit', $productCategory->id)->with('success', __('Product category added.'));
        }
        return redirect()->back()->with('error', __('Please try again.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ProductCategory $productCategory)
    {
        return view('admin.product-category.show', compact('productCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductCategory $productCategory)
    {
        return view('admin.product-category.edit', compact('productCategory'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductCategory $productCategory)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $productCategory->name        = $request->input('name');
        $productCategory->description = $request->input('description');
        $productCategory->status      = $request->input('status');

        // Slug generation
        $uniqueSlug = Str::slug($request->input('name'));
        $next       = 2;
        while (ProductCategory::where('slug', $uniqueSlug)->first()) {

            if ($request->input('name') == $productCategory->name) {
                $uniqueSlug = $productCategory->slug;
                break;
            }

            $uniqueSlug = Str::slug($request->input('name')) . '-' . $next;

            $next++;
        }
        $productCategory->slug = $uniqueSlug;

        // Thumbnail upload
        if ($request->has('thumbnail')) {
            if ($productCategory->thumbnail) {
                File::delete($productCategory->thumbnail);
            }

            $thumbnail     = $request->file('thumbnail');
            $path          = 'uploads/images/product-categories';
            $thumbnailName = time() . '_' . rand(100, 999) . '_' . $thumbnail->getClientOriginalName();
            $thumbnail->move(public_path($path), $thumbnailName);
            $productCategory->thumbnail = $thumbnailName;
        }

        if ($productCategory->save()) {
            return redirect()->route('admin.product-category.edit', $productCategory->id)->with('success', __('Product category updated.'));
        }
        return redirect()->back()->with('error', __('Please try again.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductCategory $productCategory)
    {
        if ($productCategory->delete()) {
            return redirect()->back()->with('success', __('Product category deleted.'));
        }

        return redirect()->back()->with('error', __('Please try again.'));
    }

    public function restore($id)
    {
        $productCategory = ProductCategory::onlyTrashed()->find($id);
        if ($productCategory) {
            if ($productCategory->restore()) {
                return redirect()->back()->with('success', __('Product category restored.'));
            }
            return redirect()->back()->with('error', __('Please try again.'));
        }
        return redirect()->back()->with('error', __('No product to restore.'));
    }

    public function force_delete($id)
    {
        $productCategory = ProductCategory::onlyTrashed()->find($id);
        if ($productCategory) {
            if ($productCategory->thumbnail) {
                File::delete($productCategory->thumbnail);
            }

            if ($productCategory->forceDelete()) {
                return redirect()->back()->with('success', __('Product category permanently deleted.'));
            }
            return redirect()->back()->with('error', __('Please try again.'));
        }

        return redirect()->back()->with('error', __('No product to delete.'));
    }

    public function bulk_delete(Request $request)
    {
        $item_ids = $request->input('item_ids');
        foreach ($item_ids as $id) {
            $productCategory = ProductCategory::find($id);
            if ($productCategory) {
                $productCategory->delete();
            }
        }
        return response()->json([
            'message' => 'success'
        ]);
    }

    public function bulk_force_delete(Request $request)
    {
        $item_ids = $request->input('item_ids');
        foreach ($item_ids as $id) {
            $productCategory = ProductCategory::withTrashed()->find($id);
            if ($productCategory) {
                if ($productCategory->thumbnail) {
                    File::delete($productCategory->thumbnail);
                }
                $productCategory->forceDelete();
            }
        }
        return response()->json([
            'message' => 'success'
        ]);
    }

    public function bulk_restore(Request $request)
    {
        $item_ids = $request->input('item_ids');
        foreach ($item_ids as $id) {
            $productCategory = ProductCategory::onlyTrashed()->find($id);
            if ($productCategory) {
                $productCategory->restore();
            }
        }
        return response()->json([
            'message' => 'success'
        ]);
    }

    public function bulk_active(Request $request)
    {
        $item_ids = $request->input('item_ids');
        foreach ($item_ids as $id) {
            $productCategory = ProductCategory::withTrashed()->find($id);
            if ($productCategory) {
                $productCategory->status = true;
                $productCategory->save();
            }
        }
        return response()->json([
            'message' => 'success'
        ]);
    }

    public function bulk_inactive(Request $request)
    {
        $item_ids = $request->input('item_ids');
        foreach ($item_ids as $id) {
            $productCategory = ProductCategory::withTrashed()->find($id);
            if ($productCategory) {
                $productCategory->status = false;
                $productCategory->save();
            }
        }
        return response()->json([
            'message' => 'success'
        ]);
    }

    public function export_to_excel()
    {
        return Excel::download(new ProductCategoryExport(), 'product-category.xlsx');
    }

    public function export_to_csv()
    {
        return Excel::download(new ProductCategoryExport(), 'product-category.csv', \Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv'
        ]);

    }

    public function export_to_pdf()
    {
        $productCategories = ProductCategory::latest()->get();
        $pdf               = PDF::loadView('admin.product-category.pdf', ['productCategories' => $productCategories]);
        return $pdf->download('product-category.pdf');
    }
}