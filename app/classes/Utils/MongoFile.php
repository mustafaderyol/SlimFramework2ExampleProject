<?php

namespace Utils;

use Rfd\ImageMagick\Image\Image as ImageInterface;

class MongoFile implements ImageInterface {

    protected $grid;
    protected $gridfs;
    protected $image_type;
    protected $image_id;

    public function __construct($grid, $gridfs)
    {
        $this->grid = $grid;
        $this->gridfs = $gridfs;
    }

    /**
     * Returns the complete image data, wherever it may have come from.
     *
     * @return string
     */
    public function getImageData()
    {
        $stream = $this->grid->getResource();
        $body = '';
        while (!feof($stream)) {
            $body.= fread($stream, 8192);
        }
        return $body;
    }

    /**
     * @param string $image_data
     *
     * @return void
     */
    public function setImageData($image_data)
    {
        $metadata = array(
            'filename' => $this->getFilename(),
            'mime' => $this->grid->file['mime'],
            'caption' => '',
            'description' => '',
        );
        $this->image_id = $this->gridfs->storeBytes($image_data, $metadata);

        // file_put_contents($this->filename, $image_data);
    }

    public function setFilename($filename)
    {
        return $this->targetFilename = $filename;
    }

    public function getFilename()
    {
        return empty($this->targetFilename) ? $this->grid->file['filename'] : $this->targetFilename;
    }

    public function getImageId()
    {
        return $this->image_id;
    }
}