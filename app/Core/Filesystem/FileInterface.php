<?php
namespace Core\Filesystem;

/**
 * Interface FileInterface
 */
interface FileInterface
{
    /**
     * Returns true if the file is readable, otherwise false
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Returns true if the file is writable, otherwise false
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Tells if the file is executable
     *
     * @return bool
     */
    public function isExecutable();

    /**
     * Returns the size of the file, in bytes
     *
     * @return int
     */
    public function getSize();

    /**
     * Returns the path and file name of current file
     *
     * @return string
     */
    public function getPathname();

    /**
     * Returns the path to the file, omitting the file name and any trailing slash
     *
     * @return string
     */
    public function getPath();

    /**
     * Returns the permissions of the file, as a decimal integer
     *
     * @return int
     */
    public function getPerms();

    /**
     * The file owner of the file, in numerical format
     *
     * @return int
     */
    public function getOwner();

    /**
     * Returns the group id of the current file in numerical format
     *
     * @return int
     */
    public function getGroup();

    /**
     * The last modification time of the file, as a Unix timestamp
     *
     * @return int
     */
    public function getMTime();

    /**
     * Returns the last change time of the file, as a Unix timestamp
     *
     * @return int
     */
    public function getCTime();

    /**
     * Returns the time the file was last accessed, as a Unix timestamp
     *
     * @return int
     */
    public function getATime();

    /**
     * Returns the file name
     *
     * @return string
     */
    public function getFilename();

    /**
     * Returns a string containing the file extension, or an empty string if the file has no extension
     *
     * @return string
     */
    public function getExtension();
}
