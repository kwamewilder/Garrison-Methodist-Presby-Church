<?php
/**
 * Render block-based JSON into HTML.
 */
function render_blocks($json) {
    if (!$json) {
        return '';
    }

    $blocks = [];
    $decodedOk = false;
    if (is_array($json)) {
        $blocks = $json;
        $decodedOk = true;
    } else {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = json_decode(html_entity_decode($json, ENT_QUOTES, 'UTF-8'), true);
        }
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($decoded['blocks']) && is_array($decoded['blocks'])) {
                $blocks = $decoded['blocks'];
            } elseif (is_array($decoded)) {
                $blocks = $decoded;
            }
            $decodedOk = true;
        }
    }

    if (!$decodedOk) {
        // legacy HTML or plain text fallback
        return '<div class="legacy-content">' . $json . '</div>';
    }

    $html = '';
    foreach ($blocks as $block) {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];
        $html .= render_block($type, $data);
    }
    return $html;
}

function esc($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function sanitize_align($align) {
    $align = strtolower(trim($align ?? ''));
    $allowed = ['left', 'center', 'right', 'justify'];
    return in_array($align, $allowed, true) ? $align : '';
}

/**
 * Normalize media URLs so uploads resolve correctly on the site.
 */
function normalize_media_url($urlRaw) {
    if (!$urlRaw) {
        return '';
    }

    // Absolute or protocol-relative URLs are returned unchanged
    if (preg_match('#^https?://#i', $urlRaw) || strpos($urlRaw, '//') === 0) {
        return $urlRaw;
    }

    $trimmed = ltrim($urlRaw, '/');
    $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

    if ($base) {
        return $base . '/' . $trimmed;
    }

    // Fallback to root-relative
    return '/' . $trimmed;
}

function render_block($type, $data) {
    $align = sanitize_align($data['align'] ?? '');
    $alignStyle = $align ? " style=\"text-align: {$align};\"" : '';

    switch ($type) {
        case 'heading':
            $level = max(1, min(6, (int)($data['level'] ?? 2)));
            return "<h{$level}{$alignStyle}>" . esc($data['text'] ?? '') . "</h{$level}>";
        case 'paragraph':
            return "<p{$alignStyle}>" . nl2br(esc($data['text'] ?? '')) . "</p>";
        case 'list':
            $style = $data['style'] ?? 'unordered';
            $tag = $style === 'ordered' ? 'ol' : 'ul';
            $items = $data['items'] ?? [];
            $itemsHtml = '';
            foreach ($items as $item) {
                $itemsHtml .= '<li>' . esc($item) . '</li>';
            }
            return "<{$tag} class=\"rich-list\"{$alignStyle}>{$itemsHtml}</{$tag}>";
        case 'quote':
            $text = esc($data['text'] ?? '');
            $caption = esc($data['caption'] ?? '');
            $capHtml = $caption ? "<cite>{$caption}</cite>" : '';
            return "<blockquote{$alignStyle}><p>{$text}</p>{$capHtml}</blockquote>";
        case 'image':
            $urlRaw = $data['url'] ?? '';
            $url = esc(normalize_media_url($urlRaw));
            if (!$url) return '';
            $caption = esc($data['caption'] ?? '');
            $cap = $caption ? "<figcaption>{$caption}</figcaption>" : '';
            return "<figure><img src=\"{$url}\" alt=\"{$caption}\" loading=\"lazy\">{$cap}</figure>";
        case 'video':
            $urlRaw = trim($data['url'] ?? '');
            $url = normalize_media_url($urlRaw);
            if (!$url) return '';
            $caption = esc($data['caption'] ?? '');
            $videoEmbed = render_video_media($url);
            $cap = $caption ? "<div class=\"video-caption\">{$caption}</div>" : '';
            return "<div class=\"video-block\"{$alignStyle}>{$videoEmbed}{$cap}</div>";
        case 'button':
            $text = esc($data['text'] ?? 'Learn more');
            $url = esc($data['url'] ?? '#');
            $style = ($data['style'] ?? 'primary') === 'secondary' ? 'btn-secondary' : 'btn-primary';
            return "<p><a class=\"btn {$style}\" href=\"{$url}\">{$text}</a></p>";
        case 'scripture':
            $ref = esc($data['reference'] ?? '');
            $text = esc($data['text'] ?? '');
            $version = esc($data['version'] ?? '');
            $ver = $version ? "<span class=\"scripture-version\">{$version}</span>" : '';
            return "<div class=\"scripture-block\"><blockquote>{$text}</blockquote><div class=\"scripture-ref\">{$ref} {$ver}</div></div>";
        case 'event':
            $title = esc($data['title'] ?? '');
            $date = esc($data['date'] ?? '');
            $time = esc($data['time'] ?? '');
            $location = esc($data['location'] ?? '');
            $desc = esc($data['description'] ?? '');
            return "
            <div class=\"event-block\">
                <h3>{$title}</h3>
                <p class=\"event-meta\">{$date} {$time} {$location}</p>
                <p>{$desc}</p>
            </div>";
        case 'location':
            $address = esc($data['address'] ?? '');
            $mapUrl = esc($data['mapUrl'] ?? '');
            $mapEmbed = '';
            if ($mapUrl) {
                $mapEmbed = "<iframe src=\"{$mapUrl}\" width=\"100%\" height=\"320\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>";
            } elseif ($address) {
                $query = urlencode($address);
                $mapEmbed = "<iframe src=\"https://www.google.com/maps?q={$query}&output=embed\" width=\"100%\" height=\"320\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>";
            }
            return "
            <div class=\"location-block\">
                <p class=\"location-address\"><i class=\"fas fa-map-marker-alt\"></i> {$address}</p>
                {$mapEmbed}
            </div>";
        case 'sermon':
            $title = esc($data['title'] ?? '');
            $preacher = esc($data['preacher'] ?? '');
            $date = esc($data['date'] ?? '');
            $audio = esc($data['audioUrl'] ?? '');
            $video = esc($data['videoUrl'] ?? '');
            $desc = esc($data['description'] ?? '');
            $media = '';
            if ($audio) $media .= "<a class=\"btn btn-secondary\" href=\"{$audio}\"><i class=\"fas fa-headphones\"></i> Listen</a> ";
            if ($video) $media .= "<a class=\"btn btn-primary\" href=\"{$video}\"><i class=\"fas fa-video\"></i> Watch</a>";
            return "
            <div class=\"sermon-block\">
                <h3>{$title}</h3>
                <p class=\"sermon-meta\">{$preacher} {$date}</p>
                <p>{$desc}</p>
                <div class=\"sermon-media\">{$media}</div>
            </div>";
        case 'divider':
            return '<hr class="rich-divider">';
        case 'spacer':
            $height = (int)($data['height'] ?? 24);
            $height = max(8, min(200, $height));
            return '<div class="spacer-block" style="height:' . $height . 'px"></div>';
        default:
            return '';
    }
}

function render_video_media($url) {
    $youtubePattern = '#youtu(?:\.be|be\.com)/(?:watch\\?v=|embed/|shorts/)?([A-Za-z0-9_-]{11})#i';
    $vimeoPattern = '#vimeo\\.com/(?:video/)?(\\d+)#i';

    if (preg_match($youtubePattern, $url, $m)) {
        $embed = 'https://www.youtube.com/embed/' . $m[1];
        $safe = esc($embed);
        return '<div class="video-embed"><iframe src="' . $safe . '" title="Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    }

    if (preg_match($vimeoPattern, $url, $m)) {
        $embed = 'https://player.vimeo.com/video/' . $m[1];
        $safe = esc($embed);
        return '<div class="video-embed"><iframe src="' . $safe . '" title="Video" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    }

    $safeUrl = esc($url);
    return '<div class="video-embed"><video controls preload="metadata"><source src="' . $safeUrl . '">Your browser does not support the video tag.</video></div>';
}
