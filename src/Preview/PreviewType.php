<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Preview;

enum PreviewType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Pdf = 'pdf';
    case Text = 'text';
}
