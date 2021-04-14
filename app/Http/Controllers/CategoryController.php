<?php

namespace App\Http\Controllers;

use App\Categories_ad;
use App\Category_option;
use App\Category_option_value;
use App\Favorite;
use App\SubFiveCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use App\Category;
use App\SubCategory;
use App\SubTwoCategory;
use App\SubThreeCategory;
use App\ProductImage;
use App\Product_view;
use App\Product;
use App\SubFourCategory;


class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['show_six_cat', 'getCategoryOptions', 'show_five_cat', 'show_four_cat', 'show_third_cat', 'show_second_cat', 'show_first_cat', 'getcategories', 'getAdSubCategories', 'get_sub_categories_level2', 'get_sub_categories_level3', 'get_sub_categories_level4', 'get_sub_categories_level5', 'getproducts']]);
    }

    public function getcategories(Request $request)
    {
        if ($request->lang == 'en') {
            $categories = Category::where('deleted', 0)->select('id', 'title_en as title', 'image')->get();
            for ($i = 0; $i < count($categories); $i++) {
                $categories[$i]['products_count'] = Product::where('category_id', $categories[$i]['id'])->where('status', 1)->where('publish', 'Y')->where('deleted', 0)->count();
            }
        } else {
            $categories = Category::where('deleted', 0)->select('id', 'title_ar as title', 'image')->get();
            for ($i = 0; $i < count($categories); $i++) {
                $categories[$i]['products_count'] = Product::where('category_id', $categories[$i]['id'])->where('status', 1)->where('publish', 'Y')->where('deleted', 0)->count();
            }
        }
        // $data = Categories_ad::select('image','ad_type','content as link')->where('type','category')->inRandomOrder()->take(1)->get();
        $response = APIHelpers::createApiResponse(false, 200, '', '', array('categories' => $categories), $request->lang);
        return response()->json($response, 200);
    }

    // get ad subcategories
    public function getAdSubCategories(Request $request)
    {
        if ($request->lang == 'en') {
            $data['sub_categories'] = SubCategory::where('deleted', 0)->where('category_id', $request->category_id)->select('id', 'image', 'title_en as title')->get()->toArray();
            $data['category'] = Category::select('id', 'title_en as title')->find($request->category_id);
        } else {
            $data['sub_categories'] = SubCategory::where('deleted', 0)->where('category_id', $request->category_id)->select('id', 'image', 'title_ar as title')->get()->toArray();
            $data['category'] = Category::select('id', 'title_ar as title')->find($request->category_id);
        }
        for ($i = 0; $i < count($data['sub_categories']); $i++) {
            $cat_ids[$i] = $data['sub_categories'][$i]['id'];
            $subTwoCats = SubTwoCategory::where('sub_category_id', $data['sub_categories'][$i]['id'])->where('deleted',0)->select('id')->first();
            $data['sub_categories'][$i]['next_level'] = false;
            if (isset($subTwoCats['id'])) {
                $data['sub_categories'][$i]['next_level'] = true;
            }
        }
        array_unshift($data['sub_categories']);

        // $data['ad_image'] = Categories_ad::select('image','ad_type','content as link')->wherein('cat_id',$cat_ids)->where('type','sub_category')->inRandomOrder()->take(1)->get();

        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_sub_categories_level2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }
        if ($request->lang == 'en') {
            if ($request->sub_category_id != 0) {
                $data['sub_categories'] = SubTwoCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_en as title')->get()->toArray();
                $data['sub_category_level1'] = SubCategory::where('id', $request->sub_category_id)->select('id', 'title_en as title', 'category_id')->first();
                $data['sub_category_array'] = SubCategory::where('category_id', $data['sub_category_level1']['category_id'])->select('id', 'title_en as title', 'category_id')->get()->makeHidden('category_id');
                $data['category'] = Category::where('id', $data['sub_category_level1']['category_id'])->select('id', 'title_en as title')->first();
            } else {
                $subCategories = SubCategory::where('category_id', $request->category_id)->pluck('id')->toArray();
                $data['sub_categories'] = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->select('id', 'image', 'title_en as title')->get()->toArray();
                $data['sub_category_level1'] = (object)[
                    "id" => 0,
                    "title" => "All",
                    "category_id" => $request->category_id
                ];
                $data['sub_category_array'] = SubCategory::where('category_id', $request->category_id)->select('id', 'title_en as title', 'category_id')->get()->makeHidden('category_id');
                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_en as title')->first();
            }
        } else {
            if ($request->sub_category_id != 0) {
                $data['sub_categories'] = SubTwoCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_ar as title')->get()->toArray();
                $data['sub_category_level1'] = SubCategory::where('id', $request->sub_category_id)->select('id', 'title_ar as title', 'category_id')->first();
                $data['sub_category_array'] = SubCategory::where('category_id', $data['sub_category_level1']['category_id'])->select('id', 'title_ar as title', 'category_id')->get()->makeHidden('category_id');
                $data['category'] = Category::where('id', $data['sub_category_level1']['category_id'])->select('id', 'title_ar as title')->first();
            } else {
                $subCategories = SubCategory::where('category_id', $request->category_id)->pluck('id')->toArray();
                $data['sub_categories'] = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->select('id', 'image', 'title_ar as title')->get()->toArray();
                $data['sub_category_level1'] = (object)[
                    "id" => 0,
                    "title" => "All",
                    "category_id" => $request->category_id
                ];
                $data['sub_category_array'] = SubCategory::where('category_id', $request->category_id)->select('id', 'title_ar as title', 'category_id')->get()->makeHidden('category_id');
                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_ar as title')->first();
            }
        }

        for ($i = 0; $i < count($data['sub_categories']); $i++) {
            $cat_ids[$i] = $data['sub_categories'][$i]['id'];
        }
        // $data['ad_image'] = Categories_ad::select('image','ad_type','content as link')->wherein('cat_id',$cat_ids)->where('type','sub_two_category')->inRandomOrder()->take(1)->get();
        for ($n = 0; $n < count($data['sub_category_array']); $n++) {
            if ($data['sub_category_array'][$n]['id'] == $request->sub_category_id) {
                $data['sub_category_array'][$n]['selected'] = true;
            } else {
                $data['sub_category_array'][$n]['selected'] = false;
            }
        }
        if (count($data['sub_categories']) > 0) {
            for ($i = 0; $i < count($data['sub_categories']); $i++) {
                $subThreeCats = SubThreeCategory::where('sub_category_id', $data['sub_categories'][$i]['id'])->where('deleted',0)->select('id')->first();
                $data['sub_categories'][$i]['next_level'] = false;

                if (isset($subThreeCats['id'])) {
                    $data['sub_categories'][$i]['next_level'] = true;
                }
            }
        }
        $lang = $request->lang;
        array_unshift($data['sub_categories']);
        if ($request->sub_category_id == 0) {
            $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0)->where('category_id', $request->category_id)->select('id', 'title', 'price', 'main_image as image', 'created_at', 'pin')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        } else {
            $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'title', 'price', 'main_image as image', 'created_at', 'pin')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        }
        for ($i = 0; $i < count($products); $i++) {
            if ($products[$i]['price'] == null) {
                if ($lang == 'ar') {
                    $products[$i]['price'] = 'اسأل البائع';
                } else {
                    $products[$i]['price'] = 'Ask the seller';
                }
            }
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
            $products[$i]['time'] =APIHelpers::get_month_day($products[$i]['created_at'],$lang);
        }

        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_sub_categories_level3(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);

        if ($validator->fails() && !isset($request->sub_category_level1_id)) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }

        $subCategories = SubCategory::where('category_id', $request->category_id)->pluck('id')->toArray();
        $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->pluck('id')->toArray();
        if ($request->lang == 'en') {
            if ($request->sub_category_id != 0) {
                $data['sub_categories'] = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_en as title')->get()->toArray();
                $data['sub_category_level2'] = SubTwoCategory::where('id', $request->sub_category_id)->select('id', 'title_en as title', 'sub_category_id')->first();
                if ($request->sub_category_level1_id != 0) {
                    $data['sub_category_array'] = SubTwoCategory::where('sub_category_id', $request->sub_category_level1_id)->select('id', 'title_en as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubTwoCategory::whereIn('sub_category_id', $subCategories)->select('id', 'title_en as title', 'sub_category_id')->get();
                }

                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_en as title')->first();
            } else {

                $data['sub_category_level2'] = (object)[
                    "id" => 0,
                    "title" => "All",
                    "sub_category_id" => $request->sub_category_level1_id
                ];
                if ($request->sub_category_level1_id != 0) {
                    $data['sub_category_array'] = SubTwoCategory::where('sub_category_id', $request->sub_category_level1_id)->select('id', 'title_en as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubTwoCategory::whereIn('sub_category_id', $subCategories)->select('id', 'title_en as title', 'sub_category_id')->get();
                }
                $data['sub_categories'] = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->select('id', 'image', 'title_en as title')->get()->toArray();

                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_en as title')->first();
            }
        } else {
            if ($request->sub_category_id != 0) {
                $data['sub_categories'] = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_ar as title')->get()->toArray();
                $data['sub_category_level2'] = SubTwoCategory::where('id', $request->sub_category_id)->select('id', 'title_ar as title', 'sub_category_id')->first();
                if ($request->sub_category_level1_id != 0) {
                    $data['sub_category_array'] = SubTwoCategory::where('sub_category_id', $request->sub_category_level1_id)->select('id', 'title_ar as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubTwoCategory::whereIn('sub_category_id', $subCategories)->select('id', 'title_ar as title', 'sub_category_id')->get();
                }

                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_ar as title')->first();
            } else {

                $data['sub_category_level2'] = (object)[
                    "id" => 0,
                    "title" => "All",
                    "sub_category_id" => $request->sub_category_level1_id
                ];
                if ($request->sub_category_level1_id != 0) {
                    $data['sub_category_array'] = SubTwoCategory::where('sub_category_id', $request->sub_category_level1_id)->select('id', 'title_ar as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubTwoCategory::whereIn('sub_category_id', $subCategories)->select('id', 'title_ar as title', 'sub_category_id')->get();
                }
                $data['sub_categories'] = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->select('id', 'image', 'title_ar as title')->get()->toArray();

                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_ar as title')->first();
            }
        }
        for ($i = 0; $i < count($data['sub_categories']); $i++) {
            $cat_ids[$i] = $data['sub_categories'][$i]['id'];
        }
        // $data['ad_image'] = Categories_ad::select('image','ad_type','content as link')->wherein('cat_id',$cat_ids)->where('type','sub_three_category')->inRandomOrder()->take(1)->get();

        for ($n = 0; $n < count($data['sub_category_array']); $n++) {
            if ($data['sub_category_array'][$n]['id'] == $request->sub_category_id) {
                $data['sub_category_array'][$n]['selected'] = true;
            } else {
                $data['sub_category_array'][$n]['selected'] = false;
            }
        }
        if (count($data['sub_categories']) > 0) {
            for ($i = 0; $i < count($data['sub_categories']); $i++) {
                $subThreeCats = SubFourCategory::where('sub_category_id', $data['sub_categories'][$i]['id'])->where('deleted',0)->select('id')->first();
                $data['sub_categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['sub_categories'][$i]['next_level'] = true;
                }
            }
        }

        array_unshift($data['sub_categories']);
        $products = Product::where('status', 1)->where('deleted', 0)->where('publish', 'Y')->where('category_id', $request->category_id)->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at');
        if ($request->sub_category_id != 0) {
            $products = $products->where('sub_category_two_id', $request->sub_category_id);
        }

        if ($request->sub_category_level1_id != 0) {
            $products = $products->where('sub_category_id', $request->sub_category_level1_id);
        }

        $products = $products->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);

        for ($i = 0; $i < count($products); $i++) {
            if ($products[$i]['price'] == null) {
                if ($lang == 'ar') {
                    $products[$i]['price'] = 'اسأل البائع';
                } else {
                    $products[$i]['price'] = 'Ask the seller';
                }
            }
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
            $products[$i]['time'] =APIHelpers::get_month_day($products[$i]['created_at'],$lang);
        }
        $data['products'] = $products;


        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_sub_categories_level4(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);

        if ($validator->fails() && !isset($request->sub_category_level2_id) && !isset($request->sub_category_level1_id)) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }


        if ($request->sub_category_level1_id == 0) {
            $subCategories = SubCategory::where('deleted', 0)->where('category_id', $request->category_id)->pluck('id')->toArray();
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->pluck('id')->toArray();
        } else {
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level1_id)->pluck('id')->toArray();
        }

        if ($request->sub_category_level2_id == 0) {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->pluck('id')->toArray();
        } else {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->pluck('id')->toArray();
        }

        if ($request->lang == 'en') {
            if ($request->sub_category_id != 0) {
                $data['sub_categories'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_en as title')->get()->toArray();
                $data['sub_category_level3'] = SubThreeCategory::where('id', $request->sub_category_id)->select('id', 'title_en as title', 'sub_category_id')->first();
                if ($request->sub_category_level2_id == 0) {
                    $data['sub_category_array'] = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->select('id', 'title_en as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->select('id', 'title_en as title', 'sub_category_id')->get();
                }

                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_en as title')->first();
            } else {
                $data['sub_categories'] = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->select('id', 'image', 'title_en as title')->get()->toArray();
                $data['sub_category_level3'] = (object)[
                    "id" => 0,
                    "title" => "All",
                    "sub_category_id" => $request->sub_category_level2_id
                ];
                if ($request->sub_category_level2_id == 0) {
                    $data['sub_category_array'] = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->select('id', 'title_en as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->select('id', 'title_en as title', 'sub_category_id')->get();
                }

                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_en as title')->first();
            }
        } else {
            if ($request->sub_category_id != 0) {
                $data['sub_categories'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_ar as title')->get()->toArray();
                $data['sub_category_level3'] = SubThreeCategory::where('id', $request->sub_category_id)->select('id', 'title_ar as title', 'sub_category_id')->first();
                if ($request->sub_category_level2_id == 0) {
                    $data['sub_category_array'] = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->select('id', 'title_ar as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->select('id', 'title_ar as title', 'sub_category_id')->get();
                }

                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_ar as title')->first();
            } else {
                $data['sub_categories'] = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->select('id', 'image', 'title_ar as title')->get()->toArray();
                $data['sub_category_level3'] = (object)[
                    "id" => 0,
                    "title" => "All",
                    "sub_category_id" => $request->sub_category_level2_id
                ];
                if ($request->sub_category_level2_id == 0) {
                    $data['sub_category_array'] = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->select('id', 'title_ar as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->select('id', 'title_ar as title', 'sub_category_id')->get();
                }

                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_ar as title')->first();
            }
        }


        for ($i = 0; $i < count($data['sub_categories']); $i++) {
            $cat_ids[$i] = $data['sub_categories'][$i]['id'];
        }
        // $data['ad_image'] = Categories_ad::select('image','ad_type','content as link')->wherein('cat_id',$cat_ids)->where('type','sub_four_category')->inRandomOrder()->take(1)->get();

        for ($n = 0; $n < count($data['sub_category_array']); $n++) {
            if ($data['sub_category_array'][$n]['id'] == $request->sub_category_id) {
                $data['sub_category_array'][$n]['selected'] = true;
            } else {
                $data['sub_category_array'][$n]['selected'] = false;
            }
        }

        $products = Product::where('status', 1)->where('deleted', 0)->where('publish', 'Y');
        if ($request->sub_category_id != 0) {
            $products = $products->where('sub_category_three_id', $request->sub_category_id);

        }

        if ($request->sub_category_level2_id != 0) {
            $products = $products->where('sub_category_two_id', $request->sub_category_level2_id);
        }

        if ($request->sub_category_level1_id != 0) {
            $products = $products->where('sub_category_id', $request->sub_category_level1_id);
        }

        $products = $products->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            if ($products[$i]['price'] == null) {
                if ($lang == 'ar') {
                    $products[$i]['price'] = 'اسأل البائع';
                } else {
                    $products[$i]['price'] = 'Ask the seller';
                }
            }
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
            $products[$i]['time'] =APIHelpers::get_month_day($products[$i]['created_at'],$lang);
        }
        $data['products'] = $products;


        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function get_sub_categories_level5(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);
        if ($validator->fails() && !isset($request->sub_category_level2_id) && !isset($request->sub_category_level1_id)) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }
        if ($request->sub_category_level1_id == 0) {
            $subCategories = SubCategory::where('deleted', 0)->where('category_id', $request->category_id)->pluck('id')->toArray();
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->pluck('id')->toArray();
        } else {
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level1_id)->pluck('id')->toArray();
        }
        if ($request->sub_category_level2_id == 0) {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->pluck('id')->toArray();
        } else {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->pluck('id')->toArray();
        }
        if ($request->sub_category_level3_id == 0) {
            $subCategoriesFour = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->pluck('id')->toArray();
        } else {
            $subCategoriesFour = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->pluck('id')->toArray();
        }
        if ($request->lang == 'en') {
            if ($request->sub_category_id != 0) {
                $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_en as title')->get()->toArray();
                $data['sub_category_level4'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_en as title')->first();
                if ($request->sub_category_level3_id == 0) {
                    $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->select('id', 'title_en as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->select('id', 'title_en as title', 'sub_category_id')->get();
                }
                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_en as title')->first();
            } else {
                $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->where('sub_category_id', $subCategoriesFour)->select('id', 'image', 'title_en as title')->get()->toArray();
                $data['sub_category_level3'] = (object)[
                    "id" => 0,
                    "title" => "All",
                    "sub_category_id" => $request->sub_category_level2_id
                ];
                if ($request->sub_category_level3_id == 0) {
                    $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->select('id', 'title_en as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->select('id', 'title_en as title', 'sub_category_id')->get();
                }
                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_en as title')->first();
            }
        } else {
            if ($request->sub_category_id != 0) {
                $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_ar as title')->get()->toArray();
                $data['sub_category_level4'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_id)->select('id', 'image', 'title_ar as title')->first();
                if ($request->sub_category_level3_id == 0) {
                    $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->select('id', 'title_ar as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->select('id', 'title_ar as title', 'sub_category_id')->get();
                }
                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_ar as title')->first();
            } else {
                $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->where('sub_category_id', $subCategoriesFour)->select('id', 'image', 'title_ar as title')->get()->toArray();
                $data['sub_category_level3'] = (object)[
                    "id" => 0,
                    "title" => "All",
                    "sub_category_id" => $request->sub_category_level2_id
                ];
                if ($request->sub_category_level3_id == 0) {
                    $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->select('id', 'title_ar as title', 'sub_category_id')->get();
                } else {
                    $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->select('id', 'title_ar as title', 'sub_category_id')->get();
                }
                $data['category'] = Category::where('id', $request->category_id)->select('id', 'title_ar as title')->first();
            }
        }
        for ($i = 0; $i < count($data['sub_categories']); $i++) {
            $cat_ids[$i] = $data['sub_categories'][$i]['id'];
            $subFiveCats = SubFiveCategory::where('sub_category_id', $data['sub_categories'][$i]['id'])->where('deleted', '0')->select('id', 'deleted')->first();
            $data['sub_categories'][$i]['next_level'] = false;
            if (isset($subFiveCats['id'])) {
                $data['sub_categories'][$i]['next_level'] = true;
            }
        }
        // $data['ad_image'] = Categories_ad::select('image','ad_type','content as link')->wherein('cat_id',$cat_ids)->where('type','sub_four_category')->inRandomOrder()->take(1)->get();
        for ($n = 0; $n < count($data['sub_category_array']); $n++) {
            if ($data['sub_category_array'][$n]['id'] == $request->sub_category_id) {
                $data['sub_category_array'][$n]['selected'] = true;
            } else {
                $data['sub_category_array'][$n]['selected'] = false;
            }
        }

        $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0);
        if ($request->sub_category_id != 0) {
            $products = $products->where('sub_category_four_id', $request->sub_category_id);
        }
        if ($request->sub_category_level3_id != 0) {
            $products = $products->where('sub_category_three_id', $request->sub_category_level3_id);
        }
        if ($request->sub_category_level2_id != 0) {
            $products = $products->where('sub_category_two_id', $request->sub_category_level2_id);
        }
        if ($request->sub_category_level1_id != 0) {
            $products = $products->where('sub_category_id', $request->sub_category_level1_id);
        }
        $products = $products->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            if ($products[$i]['price'] == null) {
                if ($lang == 'ar') {
                    $products[$i]['price'] = 'اسأل البائع';
                } else {
                    $products[$i]['price'] = 'Ask the seller';
                }
            }
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
            $products[$i]['time'] =APIHelpers::get_month_day($products[$i]['created_at'],$lang);
        }
        $data['products'] = $products;


        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function getproducts(Request $request)
    {
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'sub_category_level1_id' => 'required',
            'sub_category_level2_id' => 'required',
            'sub_category_level3_id' => 'required',
            'sub_category_level4_id' => 'required',
            'category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }

        if ($request->sub_category_level1_id == 0) {
            $subCategories = SubCategory::where('deleted', 0)->where('category_id', $request->category_id)->pluck('id')->toArray();
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategories)->pluck('id')->toArray();
        } else {
            $subCategoriesTwo = SubTwoCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level1_id)->pluck('id')->toArray();
        }

        if ($request->sub_category_level2_id == 0) {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesTwo)->pluck('id')->toArray();
        } else {
            $subCategoriesThree = SubThreeCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level2_id)->pluck('id')->toArray();
        }
        if ($request->sub_category_level3_id == 0) {
            $subCategoriesFour = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->pluck('id')->toArray();
        } else {
            $subCategoriesFour = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->pluck('id')->toArray();
        }
        if ($request->lang == 'en') {
            if ($request->sub_category_level4_id != 0) {
                $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->where('sub_category_id', $request->sub_category_level4_id)->select('id', 'image', 'title_en as title')->get()->toArray();
            } else {
                $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->whereIn('sub_category_id', $subCategoriesFour)->select('id', 'image', 'title_en as title')->get()->toArray();
            }
            if ($request->sub_category_level3_id != 0) {
                $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->select('id', 'image', 'title_en as title')->get()->toArray();
            } else {
                $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->select('id', 'image', 'title_en as title')->get()->toArray();
            }
        } else {
            if ($request->sub_category_level4_id != 0) {
                $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->where('sub_category_id', $request->sub_category_level4_id)->select('id', 'image', 'title_ar as title')->get()->toArray();
            } else {
                $data['sub_categories'] = SubFiveCategory::where('deleted', '0')->whereIn('sub_category_id', $subCategoriesFour)->select('id', 'image', 'title_ar as title')->get()->toArray();
            }
            if ($request->sub_category_level3_id != 0) {
                $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->where('sub_category_id', $request->sub_category_level3_id)->select('id', 'image', 'title_ar as title')->get()->toArray();
            } else {
                $data['sub_category_array'] = SubFourCategory::where('deleted', 0)->whereIn('sub_category_id', $subCategoriesThree)->select('id', 'image', 'title_ar as title')->get()->toArray();
            }
        }
        $products = Product::where('status', 1)->where('publish', 'Y')->where('deleted', 0);
        if ($request->sub_category_level1_id != 0) {
            $products = $products->where('sub_category_id', $request->sub_category_level1_id);
        }
        if ($request->sub_category_level2_id != 0) {
            $products = $products->where('sub_category_two_id', $request->sub_category_level2_id);
        }
        if ($request->sub_category_level3_id != 0) {
            $products = $products->where('sub_category_three_id', $request->sub_category_level3_id);
        }
        if ($request->sub_category_level4_id != 0) {
            $products = $products->where('sub_category_four_id', $request->sub_category_level4_id);
        }
        $products = $products->where('sub_category_five_id', $request->sub_category_id)->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at')->where('publish', 'Y')->orderBy('pin', 'DESC')->orderBy('created_at', 'desc')->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            if ($products[$i]['price'] == null) {
                if ($lang == 'ar') {
                    $products[$i]['price'] = 'اسأل البائع';
                } else {
                    $products[$i]['price'] = 'Ask the seller';
                }
            }
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
            $month = $products[$i]['created_at']->format('F');
            $products[$i]['time'] =APIHelpers::get_month_day($products[$i]['created_at'],$lang);
        }
        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }
    //nasser code
    // get ad categories for create ads
    public function show_first_cat(Request $request)
    {
        if ($request->lang == 'en') {
            $data['categories'] = Category::where('deleted', 0)->select('id', 'title_en as title', 'image')->get();
        } else {
            $data['categories'] = Category::where('deleted', 0)->select('id', 'title_ar as title', 'image')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubCategory::where('category_id', $data['categories'][$i]['id'])->where('deleted', 0)->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_second_cat(Request $request, $cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubCategory::where('category_id', $cat_id)->where('deleted', 0)->select('id', 'title_en as title', 'image')->get();
        } else {
            $data['categories'] = SubCategory::where('category_id', $cat_id)->where('deleted', 0)->select('id', 'title_ar as title', 'image')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubTwoCategory::where('sub_category_id', $data['categories'][$i]['id'])->where('deleted', 0)->where('deleted',0)->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_third_cat(Request $request, $sub_cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubTwoCategory::where('sub_category_id', $sub_cat_id)->where('deleted', 0)->select('id', 'title_en as title', 'image')->get();
        } else {
            $data['categories'] = SubTwoCategory::where('sub_category_id', $sub_cat_id)->where('deleted', 0)->select('id', 'title_ar as title', 'image')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubThreeCategory::where('sub_category_id', $data['categories'][$i]['id'])->where('deleted', 0)->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_four_cat(Request $request, $sub_sub_cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubThreeCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', 0)->select('id', 'title_en as title', 'image')->get();
        } else {
            $data['categories'] = SubThreeCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', 0)->select('id', 'title_ar as title', 'image')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubFourCategory::where('sub_category_id', $data['categories'][$i]['id'])->where('deleted', 0)->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_five_cat(Request $request, $sub_sub_cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubFourCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', 0)->select('id', 'title_en as title', 'image')->get();
        } else {
            $data['categories'] = SubFourCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', 0)->select('id', 'title_ar as title', 'image')->get();
        }
        if (count($data['categories']) > 0) {
            for ($i = 0; $i < count($data['categories']); $i++) {
                $subThreeCats = SubFiveCategory::where('sub_category_id', $data['categories'][$i]['id'])->where('deleted', '0')->select('id')->first();
                $data['categories'][$i]['next_level'] = false;
                if (isset($subThreeCats['id'])) {
                    $data['categories'][$i]['next_level'] = true;
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function show_six_cat(Request $request, $sub_sub_cat_id)
    {
        if ($request->lang == 'en') {
            $data['categories'] = SubFiveCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', '0')->select('id', 'title_en as title', 'image')->get();
        } else {
            $data['categories'] = SubFiveCategory::where('sub_category_id', $sub_sub_cat_id)->where('deleted', '0')->select('id', 'title_ar as title', 'image')->get();
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }


    // get category options
    public function getCategoryOptions(Request $request, Category $category)
    {
        if ($request->lang == 'en') {
            $data['options'] = Category_option::where('cat_id', $category['id'])->where('deleted', '0')->select('id as option_id', 'title_en as title', 'is_required')->get();

            if (count($data['options']) > 0) {
                for ($i = 0; $i < count($data['options']); $i++) {
                    $data['options'][$i]['type'] = 'input';
                    $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_en as value')->get();
                    if (count($optionValues) > 0) {

                        $data['options'][$i]['type'] = 'select';
                        $data['options'][$i]['values'] = $optionValues;
                    }
                }
            }
        } else {
            $data['options'] = Category_option::where('cat_id', $category['id'])->where('deleted', '0')->select('id as option_id', 'title_ar as title', 'is_required')->get();
            if (count($data['options']) > 0) {
                for ($i = 0; $i < count($data['options']); $i++) {
                    $data['options'][$i]['type'] = 'input';
                    $optionValues = Category_option_value::where('option_id', $data['options'][$i]['option_id'])->where('deleted', '0')->select('id as value_id', 'value_ar as value')->get();
                    if (count($optionValues) > 0) {
                        $data['options'][$i]['type'] = 'select';
                        $data['options'][$i]['values'] = $optionValues;
                    }
                }
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }


}
