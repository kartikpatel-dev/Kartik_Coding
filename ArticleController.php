<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
// use App\Models\CategoryArticle;
use App\Models\TagArticle;
use Illuminate\Http\Request;
use App\Http\Requests\ArticleValidateRequest; // use validation
use Illuminate\Support\Str;
use Auth, Session, Redirect, DataTables, File;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if( $request->ajax() ) {

            $articles = Article::latest()
                ->with(['categories' => function ($q) {
                        $q->select('category_name')->orderBy('category_name', 'ASC');
                    }])
                ->get();

            return Datatables::of($articles)
                // ->addIndexColumn()
                ->addColumn('category', function($article) {
                    $article_categories = array();
                    if( !empty($article->categories) ):
                        foreach( $article->categories as $category ):
                            array_push($article_categories, ' '.$category->category_name);
                        endforeach;
                    endif;
                    
                    return $article_categories;
                })
                ->addColumn('tag', function($article) {
                    $article_tags = array();
                    if( !empty($article->tags) ):
                        foreach( $article->tags as $tag ):
                            array_push($article_tags, ' '.$tag->tag_name);
                        endforeach;
                    endif;
                    
                    return $article_tags;
                })
                ->addColumn('created_at', function($article) {
                    return $article->getCreatedAt($article->created_at);
                })
                ->addColumn('action', function($article) {
                    $actionBtn = '<center>
                        <a href="'.route('articles.edit', $article->id).'" class="edit btn btn-success btn-sm" data-id="'.$article->id.'"><i class="fas fa-edit"></i></a>
                        <a href="javascript:;" data-id="'.$article->id.'" data-url="'.route('articles.destroy', $article->id).'" class="btn btn-sm btn-danger mx-2 delete"><i class="fas fa-trash"></i></a>
                        <a href="'.route('articles.show', $article->id).'" class="show btn bg-purple btn-sm"><i class="fas fa-eye"></i></a>
                        </center>';

                    return $actionBtn;
                })
                ->rawColumns(['category','tag', 'created_at', 'action'])
                ->make(true);
        }
        else {
            return view('articles.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = $this->categories();
        $article_categories_id = array();
        $article_subcategories_id = '';

        return view('articles.create-edit', compact('categories', 'article_categories_id', 'article_subcategories_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ArticleValidateRequest $request)
    {
        // Retrieve the validated input data...
        $validated = $request->validated();

        $response = $this->StoreUpdate($request);

        if( !empty($response) ):
            Session::flash('messageType', 'success');
            Session::flash('message', 'New article created successfully.');

            return redirect::route('articles.index');
        else:
            Session::flash('messageType', 'error');
            Session::flash('message', 'Can\'t create article.');

            return redirect::back()->withInput();
        endif;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        $article = Article::
                with(['categories' => function ($q) {
                        $q->select('category_name')->orderBy('category_name', 'ASC');
                    }])
                ->with(['sections'])
                ->findOrFail($article->id);
        
        $article_categories = array();
        if( !empty($article->categories) ):
            foreach( $article->categories as $category ):
                array_push($article_categories, $category->category_name);
            endforeach;
        endif;
        // dd($article->categories);
        // $article_categories = array_column($article->categories->toArray(), 'category_name');
        $categories = implode(', ', $article_categories);

        return view('articles.show', compact('article', 'categories'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function edit(Article $article)
    {
        $article = Article::with('categories')->findOrFail($article->id);

        $article_categories_id = array();
        $article_subcategories_id = array();
        $article_tags_id = array();

        if( !empty($article->categories) ):
            if( !empty($article->categories[0]->parent) )
            {
                array_push($article_categories_id, $article->categories[0]->parent->id);
            }
            else
            {
                if( !empty($article->categories[0]) )
                {
                    array_push($article_categories_id, $article->categories[0]->id);
                }
            }

            foreach( $article->categories as $category ):
                array_push($article_subcategories_id, $category->id);
            endforeach;

            $article_subcategories_id = implode(', ', $article_subcategories_id);
        endif;

        if( !empty($article->tags) ):
            foreach( $article->tags as $tag ):
                array_push($article_tags_id, $tag->id);
            endforeach;
        endif;

        $categories = $this->categories();

        return view('articles.create-edit', compact('article', 'article_categories_id', 'article_subcategories_id', 'article_tags_id', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(ArticleValidateRequest $request, Article $article)
    {
        // Retrieve the validated input data...
        $validated = $request->validated();

        $response = $this->StoreUpdate($request, $article->id);

        if( !empty($response) ):
            Session::flash('messageType', 'success');
            Session::flash('message', 'Article updated successfully.');

            return redirect::route('articles.index');
        else:
            Session::flash('messageType', 'error');
            Session::flash('message', 'Can\'t update article.');

            return redirect::back()->withInput();
        endif;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Article $article)
    {
        // only soft delete
        $article = Article::findOrFail($article->id)->delete();

        if( !empty($article) ):
            Session::flash('messageType', 'success');
            Session::flash('message', 'Article deleted successfully.');

            return redirect::route('articles.index');
        else:
            Session::flash('messageType', 'error');
            Session::flash('message', 'Can\'t delete article.');

            return redirect::back();
        endif;
    }

    /**
     * store and update record
    */
    private function StoreUpdate($request, $id='')
    {
        //print_r($request);
        if( !empty($id) )
        {
            $RS_Save = Article::findOrFail($id);
            $articleImage = $RS_Save->article_image;
        }
        else
        {
            $RS_Save = new Article();
            $articleImage = '';
        }

        $article_title = $request->article_title;
        // $article_slug = Str::slug($article_title, '-');
        $article_slug = $request->article_slug;

        $RS_Save->user_id = Auth::user()->id;
        $RS_Save->article_title = $article_title;
        $RS_Save->article_slug = $article_slug;
        $RS_Save->article_subtitle = $request->article_subtitle;
        $RS_Save->article_short_description = $request->article_short_description;
        // $RS_Save->article_description = $request->article_description;
        $RS_Save->meta_title = $request->meta_title;
        $RS_Save->meta_keywords = $request->meta_keywords;
        $RS_Save->meta_description = $request->meta_description;
        $RS_Save->created_at = $request->created_at;

        if( $request->hasFile('article_image') )
        {
            $image_name = $this->uploadImage($request->file('article_image'), $article_slug, $articleImage);

            $RS_Save->article_image = $image_name;
        }

        $result = $RS_Save->save();

        if( !empty($request->subcategories_id) )
        {
            $RS_Save->subcategories()->sync($request->subcategories_id);
        }
        else
        {
            $RS_Save->categories()->sync($request->categories_id);
        }

        return $result;
    }


    /**
     * upload the specified resource image.
     *
     */
    private function uploadImage($image, $article_slug, $image_name='')
    {
        $destinationPath = public_path('/uploads/');

        if( !empty($image_name) && File::exists($destinationPath.$image_name) )
        {
            File::delete($destinationPath.$image_name);
        }

        $imgName = $article_slug.'.'.$image->getClientOriginalExtension();
        $image->move($destinationPath, $imgName);

        return $imgName;
    }


    /**
     * get a listing of the category.
     */
    private function categories($parentID=0)
    {
        return ArticleCategory::where('parent_id', $parentID)->orderby('category_name', 'ASC')->pluck('category_name', 'id');
    }

    private function tags()
    {
        return ArticleTag::orderby('tag_name', 'ASC')->pluck('tag_name', 'id');
    }


    /**
     * return slug.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSlug(Request $request)
    {
        return Str::slug($request->title, '-');
    }


    /**
     * Display a get single article.
     *
     * @return \Illuminate\Http\Response
     */
    public function getArticle(Request $request)
    {
        if( $request->is('api/*') )
        {
            $RS_Article = Article::with(['sections'])
                        ->where('article_slug', $request->slug)->first();
        
            return $RS_Article ?? array();
        }
    }


    public function latestArticles(Request $request)
    {
        if( $request->is('api/*') )
        {
            return Article::with(['categories'])->take(6)->latest()->get();
        }
    }


    public function latestArticles2(Request $request)
    {
        if( $request->is('api/*') )
        {
            $article = Article::latest()->first();
            $article->timeAgo = $article->created_at->diffForHumans();
            return $article;
        }
    }


    public function categoyArticles(Request $request)
    {
        if( $request->is('api/*') )
        {
            $results = array();

            $qry = Article::latest();

            if( !empty($request->slug) )
            {
                $category = ArticleCategory::with('articles:id')->where('category_slug', $request->slug)->first();

                if( !empty($category) )
                {
                    $articles = array_column($category->articles->toArray(), 'id');
                    $qry->whereIn('id', $articles);
                }
            }

            $results = $qry->take(6)->get();

            return $results;
        }
    }
}
