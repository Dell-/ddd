<?php
namespace Core\Filesystem;

/**
 * Interface DirectoryInterface
 */
interface DirectoryInterface
{
    /**
     * Returns true if the directory is readable, otherwise false
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Returns true if the directory is writable, otherwise false
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Returns the size of the directory, in bytes
     *
     * @return int
     */
    public function getSize();

    /**
     * Returns the path and directory name of current directory
     *
     * @return string
     */
    public function getPathname();

    /**
     * Returns the path to the file, omitting the directory name and any trailing slash
     *
     * @return string
     */
    public function getPath();

    /**
     * Returns the permissions of the directory, as a decimal integer
     *
     * @return int
     */
    public function getPerms();

    /**
     * The directory owner of the directory, in numerical format
     *
     * @return int
     */
    public function getOwner();

    /**
     * Returns the group id of the current directory in numerical format
     *
     * @return int
     */
    public function getGroup();
}
