<?php

namespace ipl\Validator;

use ipl\Stdlib\Str;
use LogicException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Validates an uploaded file
 */
class FileValidator extends BaseValidator
{
    /** @var int Minimum allowed file size */
    protected int $minSize;

    /** @var ?int Maximum allowed file size */
    protected ?int $maxSize = null;

    /** @var ?string[] Allowed mime types */
    protected ?array $allowedMimeTypes;

    /** @var ?int Maximum allowed file name length */
    protected ?int $maxFileNameLength;

    /**
     * Create a new FileValidator
     *
     * Optional options:
     * - minSize: (int) Minimum allowed file size, by default 0
     * - maxSize: (int) Maximum allowed file size, by default no limit
     * - maxFileNameLength: (int) Maximum allowed file name length, by default no limit
     * - mimeType: (array) Allowed mime types, by default no restriction
     *
     * @param array{minSize?: int, maxSize?: int, maxFileNameLength?: int, mimeType?: string[]} $options
     */
    public function __construct(array $options = [])
    {
        $this
            ->setMinSize($options['minSize'] ?? 0)
            ->setMaxSize($options['maxSize'] ?? null)
            ->setMaxFileNameLength($options['maxFileNameLength'] ?? null)
            ->setAllowedMimeTypes($options['mimeType'] ?? null);
    }

    /**
     * Get the minimum allowed file size
     *
     * @return int
     */
    public function getMinSize(): int
    {
        return $this->minSize;
    }

    /**
     * Set the minimum allowed file size
     *
     * @param int $minSize
     *
     * @return $this
     */
    public function setMinSize(int $minSize): static
    {
        if (($max = $this->getMaxSize()) !== null && $minSize > $max) {
            throw new LogicException(
                sprintf(
                    'The minSize must be less than or equal to the maxSize, but minSize: %d and maxSize: %d given.',
                    $minSize,
                    $max
                )
            );
        }

        $this->minSize = $minSize;

        return $this;
    }

    /**
     * Get the maximum allowed file size
     *
     * @return ?int
     */
    public function getMaxSize(): ?int
    {
        return $this->maxSize;
    }

    /**
     * Set the maximum allowed file size
     *
     * @param ?int $maxSize
     *
     * @return $this
     */
    public function setMaxSize(?int $maxSize): static
    {
        if ($maxSize !== null && ($min = $this->getMinSize()) !== null && $maxSize < $min) {
            throw new LogicException(
                sprintf(
                    'The minSize must be less than or equal to the maxSize, but minSize: %d and maxSize: %d given.',
                    $min,
                    $maxSize
                )
            );
        }

        $this->maxSize = $maxSize;

        return $this;
    }

    /**
     * Get the allowed file mime types
     *
     * @return ?string[]
     */
    public function getAllowedMimeTypes(): ?array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Set the allowed file mime types
     *
     * @param ?string[] $allowedMimeTypes
     *
     * @return $this
     */
    public function setAllowedMimeTypes(?array $allowedMimeTypes): static
    {
        $this->allowedMimeTypes = $allowedMimeTypes;

        return $this;
    }

    /**
     * Get maximum allowed file name length
     *
     * @return ?int
     */
    public function getMaxFileNameLength(): ?int
    {
        return $this->maxFileNameLength;
    }

    /**
     * Set maximum allowed file name length
     *
     * @param ?int $maxFileNameLength
     *
     * @return $this
     */
    public function setMaxFileNameLength(?int $maxFileNameLength): static
    {
        $this->maxFileNameLength = $maxFileNameLength;

        return $this;
    }

    /**
     * @param UploadedFileInterface|UploadedFileInterface[] $value
     * @return bool
     */
    public function isValid($value): bool
    {
        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        if (is_array($value)) {
            foreach ($value as $file) {
                if (! $this->validateFile($file)) {
                    return false;
                }
            }

            return true;
        }

        return $this->validateFile($value);
    }


    private function validateFile(UploadedFileInterface $file): bool
    {
        $isValid = true;
        if ($this->getMaxSize() && $file->getSize() > $this->getMaxSize()) {
            $this->addMessage(sprintf(
                $this->translate('File %s is bigger than the allowed maximum size of %d'),
                $file->getClientFilename(),
                $this->getMaxSize()
            ));

            $isValid = false;
        }

        if ($this->getMinSize() && $file->getSize() < $this->getMinSize()) {
            $this->addMessage(sprintf(
                $this->translate('File %s is smaller than the minimum required size of %d'),
                $file->getClientFilename(),
                $this->getMinSize()
            ));

            $isValid = false;
        }

        if ($this->getMaxFileNameLength()) {
            $strValidator = new StringLengthValidator(['max' => $this->getMaxFileNameLength()]);

            if (! $strValidator->isValid($file->getClientFilename() ?? '')) {
                $this->addMessage(sprintf(
                    $this->translate('File name is longer than the allowed length of %d characters.'),
                    $this->maxFileNameLength
                ));

                $isValid = false;
            }
        }

        if (! empty($this->getAllowedMimeTypes())) {
            $hasAllowedMimeType = false;
            $fileMimetype = $file->getClientMediaType();

            if ($fileMimetype) {
                foreach ($this->getAllowedMimeTypes() as $type) {
                    if (($pos = strpos($type, '/*')) !== false) { // image/*
                        $typePrefix = substr($type, 0, $pos);
                        if (Str::startsWith($fileMimetype, $typePrefix)) {
                            $hasAllowedMimeType = true;
                            break;
                        }
                    } elseif ($fileMimetype === $type) { // image/png
                        $hasAllowedMimeType = true;
                        break;
                    }
                }
            }

            if (! $hasAllowedMimeType) {
                $this->addMessage(sprintf(
                    $this->translate('File %s is of type %s. Only %s allowed.'),
                    $file->getClientFilename(),
                    $file->getClientMediaType(),
                    implode(', ', $this->allowedMimeTypes ?? [])
                ));

                $isValid = false;
            }
        }

        return $isValid;
    }
}
