<?php

namespace App\Support;

/**
 * Renders the JobSeeder description format (plain text with headings and
 * indented bullet lines) as safe, structured HTML.
 *
 * Format: non-indented line = heading, indented lines after it = list items,
 * everything else = paragraphs. Blank lines separate blocks.
 */
class JobDescriptionFormatter
{
    public static function format(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));

        $html = '';
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = trim($lines[$i]);

            if ($line === '') {
                $i++;

                continue;
            }

            // Heading: non-indented line followed (after blanks) by an indented line.
            $next = $i + 1;
            while ($next < $count && trim($lines[$next]) === '') {
                $next++;
            }
            $hasIndentedNext = $next < $count && preg_match('/^\s+/', $lines[$next]);

            if (! preg_match('/^\s+/', $lines[$i]) && $hasIndentedNext) {
                $html .= '<h3>'.e($line).'</h3>';
                $items = [];
                $i = $next;
                while ($i < $count && preg_match('/^\s+/', $lines[$i])) {
                    $items[] = trim($lines[$i]);
                    $i++;
                }
                $html .= '<ul>'.implode('', array_map(fn ($item) => '<li>'.e($item).'</li>', $items)).'</ul>';

                continue;
            }

            // Plain paragraph.
            $html .= '<p>'.e($line).'</p>';
            $i++;
        }

        return $html;
    }
}
