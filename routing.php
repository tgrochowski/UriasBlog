<?php

// route => ControllerName:actionName e.g
// admin/user/delete/@id => User:delete
$ROUTING = [
    "" => "Front:main",
    "@id" => "Front:main",
    "tags" => "Front:tags",
    "about" => "Front:about",
    "legal_disclaimer" => "Front:legalDisclaimer",
    "contact" => "Front:contact",
    "page/@page" => "Front:main",
    "post/@date/@title" => "Front:postDetails",

    "admin" => "Admin:admin",
    "admin/users" => "Admin:userList",
    "login" => "Admin:login",
    "logout" => "Admin:logout",

    "admin/post/@id" => "Post:postDetails",
    "admin/post/delete/@id" => "Post:delete",
    "admin/post/edit/@id" => "Post:update",
    "admin/post/create" => "Post:create",
];