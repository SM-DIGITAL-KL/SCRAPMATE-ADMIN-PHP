<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;

use App\Models\StoreCategory;

class StoreController extends Controller
{
    public function store_category()
    {
        $data['pagename'] = 'Store Category';
        $data['storecategory'] = StoreCategory::all();
        return view('store/store_category', $data);
    }
    public function manage_store_cat(Request $request,$id='')
    {
        if ($request->isMethod('post')){
            if($request->post('id')!=''){
                $data1 = StoreCategory::find($request->post('id'));
            }else{
                $validated = $request->validate([
                    'category' => 'required'
                ]);
                $data1 = new StoreCategory;
            }
            $data1->name = $request->post('category');
            $data1->save();

            if ($request->post('id')!=''){
                return Redirect::to('/store_category')->with('success','Updated successfully!');
            }else{
                return Redirect::to('/store_category')->with('success','Add successfully!');
            }
        }
        if ($id!='') {
            $Storecategory = StoreCategory::find($id);
        }
        $display = '<div class="card-body">
        <div class="form-validation">
            <form action="' . route('manage_store_cat') . '" method="POST" class="needs-validation" validate>
                    ' . csrf_field() . '
                    <input type="hidden" name="id" value="' . (isset($Storecategory) ? $Storecategory->id : '') . '">
                <div class="row">
                    <label class="form-label" for="validationCustom01">Category Name<span class="text-danger">*</span></label>
                    <div class="col-lg-8">
                        <input type="text" name="category" value="' . (isset($Storecategory) ? $Storecategory->name : '') . '" class="form-control" id="validationCustom01" placeholder="Enter a name.." required>
                        <div class="invalid-feedback">Please enter a Name.</div>
                    </div>
                    <div class="col-lg-4 ms-auto">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>';
        echo $display;
    }
    public function view_store_category()
    {
        $users = DB::table('store_category')->get();
        return datatables()->of($users)
        ->addIndexColumn()
        ->addColumn('action',function ($d)
            {
                $details = '<a href="javascript:;" onclick="basic_modal('.$d->id.','."'manage_store_cat'".')" data-bs-toggle="modal" data-bs-target="#basicModal" class="btn btn-primary" title="Edit User"><i class="fas fa-pencil-alt"></i></a>';

                $details .= '&nbsp;<a href="javascript:;" onclick="custom_delete(\'/del_storecategory/' . $d->id . '\')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-sm" class="btn btn-danger" title="Delete User" ><i class="fa fa-trash"></i></a>';

                return $details;
            })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function del_storecategory($id)
    {
        $data = StoreCategory::find($id);
        if ($data) {
            $data->delete();
            return Redirect::back()->with('success','Delete successfully!');
        } else {
            return Redirect::back()->with('error','Data Not Found');
        }
    }
    public function manage_store()
    {
        $data['pagename'] = 'Manage Store';
        return view('store/manage_store', $data);
    }
    public function manage_producs()
    {
        $data['pagename'] = 'Manage Store';
        return view('store/manage_producs', $data);
    }
    public function store_report()
    {
        $data['pagename'] = 'Store Report';
        return view('store/store_report', $data);
    }
}
