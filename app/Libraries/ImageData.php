<?php

namespace App\Libraries;

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

    public static function fromJson(string $jsonStr): self
    {
        $values = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);

        $imageData = new ImageData();
        $imageData->externalId = $values['external_id'];
        $imageData->url = $values['url'];
        $imageData->rating = $values['rating'];
        $imageData->createdAt = Carbon::parse($values['created_at']);
        $imageData->updatedAt = Carbon::parse($values['updated_at']);
        $imageData->views = $values['views'];
        $imageData->source = $values['source'];

        return $imageData;
    }
}
