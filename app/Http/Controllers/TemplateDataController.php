<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * GET /templates/data/{set}.js?v={version}: sets window.templates (template list with svg) for the Builder,
 * the Sportvatar pages and the collection. Versioned URL, so browsers and Cloudflare can keep it for a year;
 * a new version appears when templates change. Sets: all | series{N}.
 */
class TemplateDataController extends Controller
{
    public function __invoke(Request $request, string $set)
    {
        abort_unless(static::isTemplateSet($set), 404);
        $version = static::templatesVersion($set);
        $current = $request->query('v') === $version;

        return response(static::templatesScript($set), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => $current ? 'public, max-age=31536000, immutable' : 'public, max-age=60, s-maxage=60',
            'X-Templates-Version' => $version,
        ]);
    }
}
