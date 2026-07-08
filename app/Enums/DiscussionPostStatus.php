<?php

namespace App\Enums;

enum DiscussionPostStatus: string
{
    case Draft = 'Draft';
    case Published = 'Published';
    case ReadOnly = 'Read Only';

    public function label(): string
    {
        return __('enums.discussion_post_status.'.$this->value);
    }
}
