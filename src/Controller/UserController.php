<?php

namespace SymfonySimpleSite\User\Controller;

use Symfony\Component\HttpFoundation\Response;

class UserController extends UserAbstractController
{
    public function index(): Response
    {
        return $this->render(
            '@User/user/index.html.twig',[
                'template' => $this->getTemplate()
            ]
        );
    }
}