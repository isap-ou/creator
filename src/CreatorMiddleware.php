<?php

namespace IsapOu\Creator;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function json_validate;
use function sprintf;
use function xdebug_break;

class CreatorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (!$response->isOk() || !$response instanceof \Illuminate\Http\Response || $request->method() !== 'GET') {
            return $response;
        }

        $content = $response->getContent();

        if (!Str::contains($content, '<head')) {
            return $response;
        }

        $creatorHtml = <<<META_EOL
<meta name="creator" content="ISAP OÜ" />
<meta name="creator-url" content="https://isap.me" />
<meta name="creator-email" content="contact@isap.me" />
META_EOL;

        $creatorHtml .= Cache::remember('isap_ou_creator_information', Carbon::now()->addDays(2), function () {
            $json = File::get(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'schema.json');
            try {
                $url = 'https://raw.githubusercontent.com/isap-ou/creator/main/schema.json';
                $options = [
                    'http' => [
                        'timeout' => 1,  // Timeout in seconds
                    ],
                ];
                $context = stream_context_create($options);
                $result = @file_get_contents($url, false, $context);

                if ($result !== false && json_validate($result)) {
                    $json = $result;
                }
            } catch (Throwable $exception) {
                // Ignore any exceptions
            }
            return sprintf('<script  type="application/ld+json">%s</script>', $json);
        });

        $content = preg_replace('/(<\/head\s*>)/i', $creatorHtml . '$1', $content);
        $response->setContent($content);

        return $response;
    }
}
