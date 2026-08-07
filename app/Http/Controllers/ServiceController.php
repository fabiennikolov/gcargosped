<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceCardResource;
use App\Models\Service;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('site/services', [
            // One grid of every service, headline ones first.
            'services' => ServiceCardResource::collection(
                Service::published()->orderByDesc('is_main')->orderBy('sort_order')->get(),
            ),
            'meta' => [
                'title' => 'Услуги — '.Setting::get('site_name'),
                'description' => 'Пълен набор от транспортни и логистични услуги: сухопътен и '
                    .'хладилен транспорт, групажни и експресни превози, складиране.',
            ],
        ]);
    }

    public function showMain(Service $service): InertiaResponse
    {
        // /service/{slug} is reserved for the three headline services; a
        // sub-service reached through it would be a duplicate URL for the same
        // content, so it 404s rather than rendering twice.
        if (! $service->is_main) {
            throw new NotFoundHttpException;
        }

        return $this->show($service);
    }

    public function showSub(Service $service): InertiaResponse
    {
        if ($service->is_main) {
            throw new NotFoundHttpException;
        }

        return $this->show($service);
    }

    private function show(Service $service): InertiaResponse
    {
        abort_unless($service->is_published, 404);

        $related = Service::published()
            ->where('id', '!=', $service->id)
            ->where('is_main', $service->is_main)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        // A main service has only two siblings, so top the list up with
        // specialised ones rather than showing a near-empty row.
        if ($related->count() < 3) {
            $related = $related->concat(
                Service::published()->sub()->orderBy('sort_order')
                    ->limit(3 - $related->count())->get(),
            );
        }

        return Inertia::render('site/service-detail', [
            'service' => [
                'slug' => $service->slug,
                'title' => $service->title,
                'subtitle' => $service->subtitle,
                'body' => $service->body,
                'image' => $service->image_url,
                'isMain' => $service->is_main,
                'url' => $service->url,
            ],
            'related' => ServiceCardResource::collection($related),
            'meta' => [
                'title' => $service->seo_title ?: $service->title.' — '.Setting::get('site_name'),
                'description' => $service->seo_description ?: $service->subtitle,
            ],
        ]);
    }
}
