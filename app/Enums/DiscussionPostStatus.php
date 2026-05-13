<?php

namespace App\Enums;

enum DiscussionPostStatus: string
{
    case Draft = 'Draft';
    case Published = 'Published';
    case ReadOnly = 'Read Only';
}
