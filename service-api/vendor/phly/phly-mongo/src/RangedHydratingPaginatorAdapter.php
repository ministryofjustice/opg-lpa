<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongo;

use MongoDB\BSON\ObjectID;

class RangedHydratingPaginatorAdapter extends HydratingPaginatorAdapter
{
    /**
     * @var HydratingMongoCursor
     */
    protected $cursor;

    /**
     * @var mixed|ObjectID
     */
    protected $currentId;

    /**
     * Creates a range based hydrating adapter when using large collections
     *
     * Instead of using MongoCursor::skip, which forces the cursor to walk
     * a ranged based query will start from the current id.
     *
     * @param HydratingMongoCursor $cursor
     * @param mixed|ObjectID $currentId
     */
    public function __construct(HydratingMongoCursor $cursor, $currentId)
    {
        parent::__construct($cursor);

        $this->currentId = $currentId;
    }

    public function getItems($offset, $itemCountPerPage)
    {
        //offset is never used in range based
        //kept here to satisfy interface
        $this->cursor->addOption('min', ['_id' => $this->currentId]);
        $this->cursor->limit($itemCountPerPage);
        return $this->cursor;
    }
}
