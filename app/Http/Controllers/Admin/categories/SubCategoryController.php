<?php

namespace App\Http\Controllers\Admin\categories;
use App\Http\Controllers\Admin\AdminController;
use JD\Cloudder\Facades\Cloudder;
use Illuminate\Http\Request;
use App\SubCategory;

class SubCategoryController extends AdminController
{

    public function index()
    {
        //
    }
    public function create($id)
    {
        return view('admin.categories.sub_catyegory.create',compact('id'));
    }
    public function store(Request $request)
    {
        $data = $this->validate(\request(),
            [
                'category_id' => 'required',
                'title_ar' => 'required',
                'title_en' => 'required',
                'image' => 'required'
            ]);

        $image_name = $request->file('image')->getRealPath();
        Cloudder::upload($image_name, null);
        $imagereturned = Cloudder::getResult();
        $image_id = $imagereturned['public_id'];
        $image_format = $imagereturned['format'];
        $image_new_name = $image_id.'.'.$image_format;
        $data['image'] = $image_new_name;
        SubCategory::create($data);

        session()->flash('success', trans('messages.added_s'));
        return redirect( route('sub_cat.show',$request->category_id));
    }
    public function show($id)
    {
        $cat_id = $id;
        $data = SubCategory::where('category_id',$id)->where('deleted','0')->get();
        return view('admin.categories.sub_catyegory.index',compact('data','cat_id'));
    }

    public function edit($id) {
        $data = SubCategory::where('id',$id)->first();
        return view('admin.categories.sub_catyegory.edit', compact('data'));
    }
    public function update(Request $request, $id) {
        $model = SubCategory::where('id',$id)->first();
        $data = $this->validate(\request(),
            [
                'title_ar' => 'required',
                'title_en' => 'required'
            ]);
        if($request->file('image')){
            $image = $model->image;
            $publicId = substr($image, 0 ,strrpos($image, "."));
            if($publicId != null ){
                Cloudder::delete($publicId);
            }
            $image_name = $request->file('image')->getRealPath();
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];
            $image_new_name = $image_id.'.'.$image_format;
            $data['image'] = $image_new_name;
        }
        SubCategory::where('id',$id)->update($data);
        session()->flash('success', trans('messages.updated_s'));
        return redirect( route('sub_cat.show',$model->category_id));
    }
    public function destroy($id)
    {
        $data['deleted'] = "1";
        SubCategory::where('id',$id)->update($data);
        session()->flash('success', trans('messages.deleted_s'));
        return back();
    }
}
