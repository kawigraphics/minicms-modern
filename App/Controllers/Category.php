<?php

namespace App\Controllers;

use App\Entities\Category as CategoryEntity;
use App\Entities\Post;
use App\Messages;
use App\Route;

class Category extends BaseController
{
    public function getCategory($categoryId, $pageNumber = 1)
    {
        $category = CategoryEntity::get($categoryId);

        if ($category === false) {
            Messages::addError("category.unknow");
            Route::redirect("blog");
        }

        $data = [
            "category" => $category,
            "posts" => $category->getPosts(["pageNumber" => $pageNumber]),
            "pagination" => [
                "pageNumber" => $pageNumber,
                "itemsCount" => Post::countAll(["category_id" => $categoryId]),
                "queryString" => Route::buildQueryString("category/$categoryId")
            ]
        ];
        $this->render("category", "Category: $category->title", $data);
    }
}
