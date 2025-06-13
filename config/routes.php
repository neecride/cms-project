<?php

return [
    // Page publique
    ['GET|POST', '/', 'home'],
    ['GET|POST', '/home', 'home', 'home'],
    ['GET|POST', '/logout', 'logout', 'logout'],
    ['GET', '/error', 'error', 'error'],
    ['GET|POST', '/remember', 'remember', 'remember'],
    ['GET|POST', '/reset-[*:username]-[*:token]', 'reset', 'reset'],
    ['GET|POST', '/register', 'register', 'register'],
    ['GET|POST', '/confirm-[*:username]-[*:token]', 'confirm', 'confirm'],
    ['GET|POST', '/login', 'login', 'login'],

    // Compte utilisateur
    ['GET|POST', '/account', 'account', 'account'],

    // Forum
    ['GET', '/forum', 'forum', 'forum'],
    ['GET', '/forum-viewforum-[*:slug]-[i:id]', 'viewforums', 'forum-tags'],
    ['GET|POST', '/forum-topic-[i:id]', 'viewtopic', 'viewtopic'],
    ['GET|POST', '/sticky-[i:id]-[i:sticky]-[*:getcsrf]', 'viewtopic', 'sticky'],
    ['GET|POST', '/lock-[i:id]-[i:lock]-[*:getcsrf]', 'viewtopic', 'lock'],
    ['GET|POST', '/unlock-[i:id]-[i:lock]-[*:getcsrf]', 'viewtopic', 'unlock'],
    ['GET|POST', '/creattopic', 'creattopic', 'creattopic'],
    ['GET|POST', '/edittopic-[i:id]', 'edittopic', 'edittopic'],
    ['GET|POST', '/editrep-[i:id]', 'editrep', 'editrep'],

    // Admin
    ['GET|POST', '/admin/dashboard', 'admin', 'admin'],
    ['GET', '/admin/user', 'user', 'user'],
    ['GET|POST', '/admin/user-edit-[i:id]-[*:getcsrf]', 'useredit', 'user-edit'],
    ['GET|POST', '/admin/user-delete-[i:del]-[i:rank]-[*:getcsrf]', 'user', 'user-delete'],
    ['GET|POST', '/admin/user-active-[i:activ]-[i:rank]-[*:getcsrf]', 'user', 'user-active'],
    ['GET|POST', '/admin/user-desactive-[i:unactiv]-[i:rank]-[*:getcsrf]', 'user', 'user-desactive'],
    ['GET', '/admin/tags', 'tags', 'tags'],
    ['GET|POST', '/admin/tags-add', 'tagsedit', 'tags-add'],
    ['GET|POST', '/admin/tags-edit-[*:editid]-[*:getcsrf]', 'tagsedit', 'tags-edit'],
    ['GET|POST', '/admin/widget-alert', 'widgetalert', 'widget-alert'],
];