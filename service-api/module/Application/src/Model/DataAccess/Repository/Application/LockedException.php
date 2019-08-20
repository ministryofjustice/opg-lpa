<?php
namespace Application\Model\DataAccess\Repository\Application;

/**
 * Thrown if a an amend is attempted on a locked LPA.
 *
 * Class LockedException
 * @package Application\Model\DataAccess\Repository\Application
 */
class LockedException extends \RuntimeException
{}
