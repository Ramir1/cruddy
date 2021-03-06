<?php

namespace Kalnoy\Cruddy\Schema\Fields\Types;

use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Handles morph to many relation.
 *
 * @since 1.0.0
 */
class MorphToMany extends BelongsToMany {

    /**
     * {@inheritdoc}
     */
    protected function initNestedQuery(QueryBuilder $query, array $ids)
    {
        parent::initNestedQuery($query, $ids);

        $query->where($this->relation->getMorphType(), '=', $this->relation->getMorphClass());
    }

}