<?php

namespace App\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ImageData
{
    private int $id;

    private string $rating;

    private Carbon $createdAt;

    private Carbon $updatedAt;

    private int $views;

    private ?string $source;

    private ?string $imageFileData;

    private string $fileExtension;

    private string $mimetype;

    public function getId(): int
    {
        return $this->id;
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

    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    public function setFileExtension(string $fileExtension): void
    {
        $this->fileExtension = $fileExtension;
    }

    public function getMimetype(): string
    {
        return $this->mimetype;
    }

    public function setMimetype(string $mimetype): void
    {
        $this->mimetype = $mimetype;
    }

    public static function fromJson(array $jsonData): self
    {
        $imageData = new ImageData();
        $imageData->id = Arr::get($jsonData, 'id');
        $imageData->rating = Arr::get($jsonData, 'rating');
        $imageData->createdAt = Carbon::parse(Arr::get($jsonData, 'created_at'));
        $imageData->updatedAt = Carbon::parse(Arr::get($jsonData, 'updated_at'));
        $imageData->views = Arr::get($jsonData, 'views');
        $imageData->source = Arr::get($jsonData, 'source');
        $imageData->fileExtension = Arr::get($jsonData, 'file_extension');
        $imageData->mimetype = Arr::get($jsonData, 'mimetype');

        return $imageData;
    }
}
