<?php

declare(strict_types=1);

namespace MakeShared\OneLogin;

enum UserType: string
{
    case Lay = 'lay';
    case Professional = 'professional';
}
