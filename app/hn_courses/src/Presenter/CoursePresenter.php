<?php
declare(strict_types=1);

namespace HnCourses\Presenter;

final class CoursePresenter
{
    public function present(array $data): array
    {
        $course = $data['course'];

        $resources = [
            'documents' => [],
            'links'     => [],
            'youtube'   => [],
            'videos'    => [],
        ];

        foreach ($data['resources'] as $r) {

            switch ($r['resource_type']) {

                case 'file':
                    $resources['documents'][] = [
                        'title' => $r['title'],
                        'file'  => $r['stored_filename'],
                    ];
                    break;

                case 'link':
                    $resources['links'][] = [
                        'title' => $r['title'],
                        'url'   => $r['external_url'],
                    ];
                    break;

                case 'youtube':
                    $resources['youtube'][] = [
                        'title' => $r['title'],
                        'id'    => $r['youtube_id'],
                    ];
                    break;
            }
        }

        return [
            'title'       => $course['title'],
            'description' => $course['description'],
            'schedule'    => $this->formatEvents($data['events']),
            'resources'   => $resources,
        ];
    }

    private function formatEvents(array $events): array
    {
        return array_map(function ($e) {
            return [
                'date'     => date('d.m.Y', strtotime($e['start_datetime'])),
                'time'     => date('H:i', strtotime($e['start_datetime'])),
                'title'    => $e['summary'] ?? '',
                'location' => $e['location'] ?? '',
            ];
        }, $events);
    }
}