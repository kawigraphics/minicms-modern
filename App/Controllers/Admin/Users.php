<?php

namespace App\Controllers\Admin;

use App\Config;
use App\Form;
use App\Lang;
use App\Renderer;
use App\Router;
use App\Session;
use App\Validator;
use App\Entities\Repositories\User as UserRepo;

class Users extends AdminBaseController
{
    /**
     * @var UserRepo
     */
    public $userRepo;

    public function __construct(
        Lang $lang, Session $session, Validator $validator, Router $router, Renderer $renderer, Config $config, Form $form,
        UserRepo $userRepo)
    {
        parent::__construct($lang, $session, $validator, $router, $renderer, $config, $form);
        $this->userRepo = $userRepo;
    }

    public function getRead(int $pageNumber = 1)
    {
        if ($this->user->isCommenter()) {
            $this->router->redirect("admin/users/update/" . $this->user->id);
            return;
        }

        $allRows = $this->userRepo->getAll(["pageNumber" => $pageNumber]);

        $data = [
            "allRows" => $allRows,
            "pagination" => [
                "pageNumber" => $pageNumber,
                "itemsCount" => $this->userRepo->countAll(),
                "queryString" => $this->router->getQueryString("admin/users/read/")
            ],
        ];
        $this->render("users.read", $data);
    }

    public function getCreate()
    {
        if (!$this->user->isAdmin()) {
            $this->router->redirect("admin/users");
            return;
        }

        $this->render("users.update", ["action" => "create"]);
    }

    public function postCreate()
    {
        if (!$this->user->isAdmin()) {
            $this->router->redirect("admin/users/read");
            return;
        }

        $post = $this->validator->sanitizePost([
            "name" => "string",
            "email" => "string",
            "password" => "string",
            "password_confirmation" => "string",
            "role" => "string",
        ]);
        $post["email_token"] = ""; // a new email token will not be generated by UserRepo::create()

        if ($this->validator->csrf("usercreate")) {
            if ($this->validator->user($post)) {
                $user = $this->userRepo->create($post);

                if (is_object($user)) {
                    $this->session->addSuccess("user.created");
                    $this->router->redirect("admin/users/update/$user->id");
                    return;
                } else {
                    $this->session->addError("db.createuser");
                }
            }
        } else {
            $this->session->addError("csrffail");
        }

        $data = [
            "action" => "create",
            "post" => $post,
        ];
        $this->render("users.update", $data);
    }

    public function getUpdate(int $userId)
    {
        if (! $this->user->isAdmin() && $userId !== $this->user->id) {
            $this->router->redirect("admin/users/update/" . $this->user->id);
            return;
        }

        $user = $this->userRepo->get($userId);
        if ($user === false) {
            $this->session->addError("user.unknown");
            $this->router->redirect("admin/users/read");
            return;
        }

        $data = [
            "action" => "update",
            "post" => $user->toArray(),
        ];
        $this->render("users.update", $data);
    }

    public function postUpdate(int $userId)
    {
        if (! $this->user->isAdmin() && $userId !== $this->user->id) {
            $this->router->redirect("admin/users/update/" . $this->user->id);
            return;
        }

        $schema = [
            "name" => "string",
            "email" => "string",
            "password" => "string",
            "password_confirmation" => "string",
        ];

        if ($this->user->isAdmin()) {
            $schema = array_merge($schema, [
                "role" => "string",
                "email_token" => "string",
                "password_token" => "string",
                "password_change_time" => "int",
                "is_blocked" => "checkbox",
            ]);
        } else {
            $post["role"] = $this->user->role;
        }

        $post = $this->validator->sanitizePost($schema);
        $post["id"] = $userId;

        if ($this->validator->csrf("userupdate")) {
            if ($this->validator->user($post)) {
                $user = $this->userRepo->get($post["id"]);

                if (is_object($user)) {
                    if ($user->update($post)) {
                        $this->session->addSuccess("user.updated");
                        $this->router->redirect("admin/users/update/$user->id");
                        return;
                    } else {
                        $this->session->addError("db.userupdated");
                    }
                } else {
                    $this->session->addError("user.unknown");
                }
            }
        } else {
            $this->session->addError("csrffail");
        }

        $post["creation_datetime"] = $this->userRepo->get($post["id"])->creation_datetime;

        $data = [
            "action" => "update",
            "post" => $post,
        ];
        $this->render("users.update", $data);
    }
    public function postDelete(int $userId)
    {
        if ($this->user->isAdmin()) {
            if ($this->user->id !== $userId) {
                if ($this->validator->csrf("userdelete$userId")) {
                    $user = $this->userRepo->get($userId);
                    if (is_object($user)) {
                        if ($user->deleteByAdmin($this->user->id)) {
                            $this->session->addSuccess("user.deleted");
                        } else {
                            $this->session->addError("user.deleting");
                        }
                    } else {
                        $this->session->addError("user.unknown");
                    }
                } else {
                    $this->session->addError("csrffail");
                }
            } else {
                $this->session->addError("user.cantdeleteownuser");
            }
        }

        $this->router->redirect("admin/users/read");
    }
}
