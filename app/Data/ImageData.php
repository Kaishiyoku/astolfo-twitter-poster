<?php

namespace App\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ImageData
{
    private int $externalId;

    private string $url;

    private string $rating;

    private Carbon $createdAt;

    private Carbon $updatedAt;

    private int $views;

    private ?string $source;

    private ?string $imageFileData;

    public function getExternalId(): int
    {
        return $this->externalId;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getRating(): string
    {
        return $this->rating;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getImageFileData(): ?string
    {
        return $this->imageFileData;
    }

    public function setImageFileData(?string $imageFileData): void
    {
        $this->imageFileData = $imageFileData;
    }

    public static function fromJson(array $jsonData): self
    {
        $imageData = new ImageData();
        $imageData->externalId = Arr::get($jsonData, 'external_id');
        $imageData->url = Arr::get($jsonData, 'url');
        $imageData->rating = Arr::get($jsonData, 'rating');
        $imageData->createdAt = Carbon::parse(Arr::get($jsonData, 'created_at'));
        $imageData->updatedAt = Carbon::parse(Arr::get($jsonData, 'updated_at'));
        $imageData->views = Arr::get($jsonData, 'views');
        $imageData->source = Arr::get($jsonData, 'source');

        return $imageData;
    }
}
