<?php
declare(strict_types=1);
/**
 * Image uploader model
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ImageUploader
{
    /**
     * Path constants to avoid hardcoded strings
     */
    const BASE_TMP_PATH = 'wholesale/partner/tmp';
    const BASE_PATH = 'wholesale/partner';
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png'];

    /**
     * Core file storage database
     *
     * @var Database
     */
    protected $coreFileStorageDatabase;

    /**
     * Media directory object (writable)
     *
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * Uploader factory
     *
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Base tmp path
     *
     * @var string
     */
    protected $baseTmpPath;

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath;

    /**
     * Allowed extensions
     *
     * @var string[]
     */
    protected $allowedExtensions;

    /**
     * @param Database $coreFileStorageDatabase
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param string $baseTmpPath
     * @param string $basePath
     * @param string[] $allowedExtensions
     * @throws Exception
     */
    public function __construct(
        Database $coreFileStorageDatabase,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        $baseTmpPath = self::BASE_TMP_PATH,
        $basePath = self::BASE_PATH,
        array $allowedExtensions = self::ALLOWED_EXTENSIONS
    ) {
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->baseTmpPath = $baseTmpPath;
        $this->basePath = $basePath;
        $this->allowedExtensions = $allowedExtensions;
    }

    /**
     * Set base tmp path
     *
     * @param string $baseTmpPath
     * @return void
     */
    public function setBaseTmpPath(string $baseTmpPath): void
    {
        $this->baseTmpPath = $baseTmpPath;
    }

    /**
     * Set base path
     *
     * @param string $basePath
     * @return void
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * Set allowed extensions
     *
     * @param string[] $allowedExtensions
     * @return void
     */
    public function setAllowedExtensions(array $allowedExtensions): void
    {
        $this->allowedExtensions = $allowedExtensions;
    }

    /**
     * Retrieve base tmp path
     *
     * @return string
     */
    public function getBaseTmpPath(): string
    {
        return $this->baseTmpPath;
    }

    /**
     * Retrieve base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Retrieve allowed extensions
     *
     * @return string[]
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * Retrieve path
     *
     * @param string $path
     * @param string $imageName
     * @return string
     */
    public function getFilePath(string $path, string $imageName): string
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    /**
     * Get file URL in media directory
     *
     * @param string $imageName
     * @return string
     */
    public function getMediaUrl(string $imageName): string
    {
        return $this->storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . $this->getFilePath($this->basePath, $imageName);
    }

    /**
     * Delete file from media directory
     *
     * @param string $imageName
     * @return bool
     * @throws LocalizedException
     */
    public function deleteFile(string $imageName): bool
    {
        $basePath = $this->getBasePath();
        $path = $this->getFilePath($basePath, $imageName);
        
        if ($this->mediaDirectory->isExist($path)) {
            try {
                $this->mediaDirectory->delete($path);
                return true;
            } catch (FileSystemException $e) {
                throw new LocalizedException(
                    __('Could not delete file: %1', $e->getMessage())
                );
            }
        }
        
        return false;
    }

    /**
     * Checking file for moving and move it
     *
     * @param string $imageName
     * @return string
     * @throws LocalizedException
     */
    public function moveFileFromTmp(string $imageName): string
    {
        $baseTmpPath = $this->getBaseTmpPath();
        $basePath = $this->getBasePath();

        $baseImagePath = $this->getFilePath($basePath, $imageName);
        
        // Account for file dispersion structure
        $firstChar = strtolower(substr($imageName, 0, 1));
        $secondChar = strtolower(substr($imageName, 1, 1));
        $dispersedTmpPath = $baseTmpPath . '/' . $firstChar . '/' . $secondChar . '/' . $imageName;
        
        // Check both dispersed and non-dispersed paths
        $baseTmpImagePath = $this->mediaDirectory->isExist($dispersedTmpPath)
            ? $dispersedTmpPath
            : $this->getFilePath($baseTmpPath, $imageName);

        try {
            // Check if source file exists
            if (!$this->mediaDirectory->isExist($baseTmpImagePath)) {
                throw new LocalizedException(
                    __('Image "%1" does not exist in the temporary directory.', $imageName)
                );
            }
            
            // Normalise the image name to lowercase for consistency
            $normalizedImageName = strtolower(basename($imageName));
            $baseImagePath = $this->getFilePath($basePath, $normalizedImageName);
            
            // Create the destination directory if it doesn't exist
            $dirPath = dirname($baseImagePath);
            if (!$this->mediaDirectory->isExist($dirPath)) {
                $this->mediaDirectory->create($dirPath);
            }
            
            // Copy to permanent storage first
            $this->coreFileStorageDatabase->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            
            // Then move from temporary to permanent location (renames the file)
            $this->mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            
            // Verify the file was correctly moved
            if (!$this->mediaDirectory->isExist($baseImagePath)) {
                throw new LocalizedException(
                    __('Failed to move image "%1" to the permanent directory.', $normalizedImageName)
                );
            }
            
        } catch (Exception $e) {
            $this->logger->error(
                'Error moving file from tmp directory: ' . $e->getMessage(),
                [
                    'file' => $imageName,
                    'error' => $e->getMessage()
                ]
            );
            throw new LocalizedException(
                __('Something went wrong while moving the file(s): %1', $e->getMessage())
            );
        }

        // Always return the normalized image name so it can be saved consistently in the database
        return $normalizedImageName;
    }

    /**
     * Checking file for save and save it to tmp dir
     *
     * @param string $fileId
     * @return array
     * @throws LocalizedException
     */
    public function saveFileToTmpDir(string $fileId): array
    {
        $baseTmpPath = $this->getBaseTmpPath();

        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions($this->getAllowedExtensions());
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);

        try {
            $result = $uploader->save($this->mediaDirectory->getAbsolutePath($baseTmpPath));
        } catch (Exception $e) {
            $this->logger->error('Failed to upload file to tmp directory: ' . $e->getMessage());
            throw new LocalizedException(
                __('File cannot be saved to the destination folder: %1', $e->getMessage())
            );
        }

        if (!$result) {
            throw new LocalizedException(
                __('File cannot be saved to the destination folder.')
            );
        }

        /**
         * Workaround for prototype 1.7 methods "isJSON", "evalJSON" on Windows OS
         */
        $result['tmp_name'] = str_replace('\\', '/', $result['tmp_name']);
        $result['path'] = str_replace('\\', '/', $result['path']);
        $result['url'] = $this->storeManager
                ->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $this->getFilePath($baseTmpPath, $result['file']);

        if (isset($result['file'])) {
            try {
                $relativePath = rtrim($baseTmpPath, '/') . '/' . ltrim($result['file'], '/');
                $this->coreFileStorageDatabase->saveFile($relativePath);
            } catch (Exception $e) {
                $this->logger->error('Error saving file to database: ' . $e->getMessage());
                throw new LocalizedException(
                    __('Something went wrong while saving the file(s).')
                );
            }
        }

        return $result;
    }
}