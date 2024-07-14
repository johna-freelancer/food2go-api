<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PaginateCollection extends ResourceCollection
{
    private $pagination;
    private $message;

    public function __construct($resource, $message = '')
    {
        $this->pagination = [
            'total' => $resource->total(),
            'limit' => $resource->perPage(),
            'current' => $resource->currentPage(),
            'pages' => $resource->lastPage(),
        ];

        $this->message = $message;

        $resource = $resource->getCollection(); // Necessary to remove meta and links

        parent::__construct($resource);
    }

    public function toArray($request)
    {
        return [
            'status' => 'success',
            'data' => $this->collection,
            'pagination' => $this->pagination,
            'message' => $this->message
        ];
    }
}
