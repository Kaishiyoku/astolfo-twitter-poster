<?php

namespace App\Libraries;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ImageData
{
    /**
     * @var int
     */
    private $externalId;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $rating;

    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var Carbon
     */
    private $updatedAt;

    /**
     * @var int
     */
    private $views;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var string|null;
     */
    private $imageFileData;

    /**
     * @return int
     */
    public function getExternalId(): int
    {
        return $this->externalId;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getRating(): string
    {
        return $this->rating;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * @return Carbon
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    /**
     * @return int
     */
    public function getViews(): int
    {
        return $this->views;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @return string|null
     */
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
