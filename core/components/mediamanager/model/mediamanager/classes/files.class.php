<?php

class MediaManagerFilesHelper
{
    /**
     * The mediaManager object.
     */
    private $mediaManager = null;

    /**
     * The mediaSource object.
     */
    private $mediaSource = null;

    /**
     * Upload paths.
     */
    private $uploadUrl = null;
    private $uploadDirectory = null;
    private $uploadDirectoryYear = null;
    private $uploadDirectoryMonth = null;

    private $sortOptions = array();

    /**
     * MediaManagerFilesHelper constructor.
     *
     * @param MediaManager $mediaManager
     */
    public function __construct(MediaManager $mediaManager)
    {
        $this->mediaManager = $mediaManager;

        $this->setSortOptions();
    }

    /**
     * Get files.
     *
     * @param string $search
     * @param array $filters
     * @param array $sorting
     *
     * @return array
     */
    public function getList($search = '', $filters = array(), $sorting = array())
    {
        $sortColumn = 'upload_date';
        $sortDirection = 'DESC';
        $where = array(
            'mediamanager_contexts_id' => $this->mediaManager->contexts->getCurrentContext(),
            'is_archived' => 0
        );

        if (!empty($search) && strlen($search) > 2) {
            $where['name:LIKE'] = '%' . $search . '%';
        }

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $where[$key] = $value;
            }
        }

        if (!empty($sorting)) {
            $sortColumn = $sorting[0];
            $sortDirection = $sorting[1];
        }

        $q = $this->mediaManager->modx->newQuery('MediamanagerFiles');
        $q->where($where);
        $q->sortby($sortColumn, $sortDirection);

        return $this->mediaManager->modx->getIterator('MediamanagerFiles', $q);
    }

    /**
     * Get files html.
     *
     * @param string $search
     * @param array $filters
     * @param array $sorting
     *
     * @return string
     */
    public function getListHtml($search = '', $filters = array(), $sorting = array())
    {
        $files = $this->getList($search, $filters, $sorting);

        $html = '';
        foreach ($files as $file) {
            $html .= $this->mediaManager->getChunk('files/file', $file->toArray());
        }

        if (empty($html)) {
            $html = $this->mediaManager->modx->lexicon('mediamanager.files.error.no_files_found');
        }

        return $html;
    }

    /**
     * Get sort options html.
     *
     * @return string
     */
    public function getSortOptionsHtml()
    {
        $html = '';
        foreach ($this->sortOptions as $option) {
            $html .= $this->mediaManager->getChunk('files/sort_option', $option);
        }

        return $html;
    }

    /**
     * Set sort options.
     */
    private function setSortOptions()
    {
        $this->sortOptions = array(
            array(
                'name' => 'Date new - old', // @TODO: Add lexicons
                'field' => 'upload_date',
                'direction' => 'DESC'
            ),
            array(
                'name' => 'Date old - new',
                'field' => 'upload_date',
                'direction' => 'ASC'
            ),
            array(
                'name' => 'Name A - Z',
                'field' => 'name',
                'direction' => 'ASC'
            ),
            array(
                'name' => 'Name Z - A',
                'field' => 'name',
                'direction' => 'DESC'
            )
        );
    }

    /**
     * Add file.
     *
     * @return bool
     */
    public function addFile()
    {
        // Get file
        $file = $_FILES['file'];

        // Get media source settings
        $source = $this->mediaManager->modx->getObject('modMediaSource', $this->mediaManager->modx->getOption('mediamanager.media_source'));
        $this->mediaSource = json_decode(json_encode($source->getProperties()));

        // Set upload directory, year and month
        $year = date('Y');
        $month = date('m');

        $this->uploadUrl            = $this->addSlashes($this->mediaSource->baseUrl->value) .
                                      $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR;
        $this->uploadDirectory      = $this->addTrailingSlash(MODX_BASE_PATH) .
                                      $this->addTrailingSlash(trim($this->mediaSource->basePath->value, DIRECTORY_SEPARATOR));
        $this->uploadDirectoryYear  = $this->uploadDirectory . $year . DIRECTORY_SEPARATOR;
        $this->uploadDirectoryMonth = $this->uploadDirectoryYear . $month . DIRECTORY_SEPARATOR;

        // Create upload directory
        if (!$this->createUploadDirectory()) {
            return [
                'error'   => true,
                'message' => 'Could not create upload directory.'
            ];
        }

        // Check if file hash exists
        $file['hash'] = $this->getFileHashByPath($file['tmp_name']);

        if ($this->fileHashExists($file['hash'])) {
            return [
                'error'   => true,
                'message' => 'File already exists.'
            ];
        }

        // Add unique id to file name if needed
        $fileInformation = pathinfo($file['name']);
        $fileName = $this->createUniqueFile($this->sanitizeFileName($fileInformation['filename']), $fileInformation['extension']);

        $file['extension'] = $fileInformation['extension'];
        $file['unique_name'] = $fileName;

        // Upload file
        if (!$this->uploadFile($file)) {
            return [
                'error'   => true,
                'message' => 'File could not be uploaded.'
            ];
        }

        // Add file to database
        if (!$this->insertFile($file)) {
            // @TODO: Remove file from sever
            return [
                'error'   => true,
                'message' => 'File not added to database.'
            ];
        }

        return [
            'error'   => false,
            'message' => 'File uploaded.'
        ];
    }

    private function insertFile($data) {
        $file = $this->mediaManager->modx->newObject('MediamanagerFiles');

        $file->set('name', $data['unique_name']);
        $file->set('path', $this->uploadUrl . $data['unique_name']);
        $file->set('file_type', $data['extension']);
        $file->set('file_size', $data['size']);
        $file->set('file_hash', $data['hash']);
        $file->set('uploaded_by', $this->mediaManager->modx->getUser()->get('id'));
        $file->set('mediamanager_contexts_id', $this->mediaManager->contexts->getCurrentContext());

        // If file type is image set dimensions
        if (false) {
            // getimagesize()
            $dimensions = '';
            $file->set('file_dimensions', $dimensions);
        }

        $file->save();

        return true;
    }

    /**
     * Create unique file name.
     *
     * @param string $name
     * @param string $extension
     * @param string $uniqueId
     *
     * @return string
     */
    private function createUniqueFile($name, $extension, $uniqueId = '') {
        $file = $this->uploadDirectoryMonth . $name . $uniqueId . '.' . $extension;
        if (file_exists($file)) {
            $uniqueId = uniqid('-');
            return $this->createUniqueFile($name, $extension, $uniqueId);
        }

        return $name . $uniqueId . '.' . $extension;
    }

    /**
     * Create upload directory if not exists.
     *
     * @return bool
     */
    private function createUploadDirectory()
    {
        if (!file_exists($this->uploadDirectory)) {
            if (!$this->createDirectory($this->uploadDirectory)) return false;
        }

        if (!file_exists($this->uploadDirectoryYear)) {
            if (!$this->createDirectory($this->uploadDirectoryYear)) return false;
        }

        if (!file_exists($this->uploadDirectoryMonth)) {
            if (!$this->createDirectory($this->uploadDirectoryMonth)) return false;
        }

        return true;
    }

    /**
     * Create directory.
     *
     * @param string $directoryPath
     * @param int $mode
     *
     * @return bool
     */
    private function createDirectory($directoryPath, $mode = 0755)
    {
        return mkdir($directoryPath, $mode);
    }

    /**
     * Upload file.
     *
     * @param array $file
     * @return bool
     */
    private function uploadFile($file)
    {
        $target = $this->uploadDirectoryMonth . $file['unique_name'];
        $uploadFile = move_uploaded_file($file['tmp_name'], $target);
        if ($uploadFile) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize file name.
     * Only allow a-z, 0-9, _ and -
     *
     * @param string $fileName
     * @return string
     */
    public function sanitizeFileName($fileName)
    {
        $find = array(
            ' '
        );

        $replace = array(
            '-'
        );

        $fileName = strtolower($fileName);
        $fileName = str_replace($find, $replace, $fileName);
        $fileName = preg_replace('/[^a-z0-9_-]+/', '', $fileName);

        return $fileName;
    }

    /**
     * Get file hash.
     *
     * @param string $filePath
     * @return string
     */
    public function getFileHashByPath($filePath)
    {
        return md5_file($filePath);
    }

    /**
     * Check if file hash exists.
     *
     * @param string $fileHash
     * @return bool
     */
    public function fileHashExists($fileHash)
    {
        $fileObject = $this->mediaManager->modx->getObject('MediamanagerFiles',
            array(
                'file_hash' => $fileHash
            )
        );

        if ($fileObject === null) {
            return false;
        }

        return true;
    }

    /**
     * Add slashes to string.
     *
     * @param string $string
     * @return string
     */
    public function addSlashes($string)
    {
        return DIRECTORY_SEPARATOR . trim($string, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Add trailing slash to string.
     *
     * @param string $string
     * @return string
     */
    public function addTrailingSlash($string)
    {
        return rtrim($string, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}