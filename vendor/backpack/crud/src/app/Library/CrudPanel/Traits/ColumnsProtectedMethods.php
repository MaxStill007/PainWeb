<?php

namespace Backpack\CRUD\app\Library\CrudPanel\Traits;

use Backpack\CRUD\app\Library\CrudPanel\CrudColumn;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait ColumnsProtectedMethods
{
    /**
     * Add a column to the current operation, using the Setting API.
     *
     * @param  array  $column  Column definition array.
     */
    protected function addColumnToOperationSettings($column)
    {
        $allColumns = $this->columns();
        $allColumns = Arr::add($allColumns, $column['key'], $column);

        $this->setOperationSetting('columns', $allColumns);
    }

    /**
     * If a column priority has not been defined, provide a default one.
     *
     * @param  array  $column  Column definition array.
     * @return array Proper array defining the column.
     */
    protected function makeSureColumnHasPriority($column)
    {
        $columns_count = $this->countColumnsWithoutActions();
        $assumed_priority = $columns_count ? $columns_count : 0;

        $column['priority'] = $column['priority'] ?? $assumed_priority;

        return $column;
    }

    /**
     * If the field definition array is actually a string, it means the programmer was lazy
     * and has only passed the name of the column. Turn that into a proper array.
     *
     * @param  array  $column  Column definition array.
     * @return array Proper array defining the column.
     */
    protected function makeSureColumnHasName($column)
    {
        if (is_string($column)) {
            return ['name' => Str::replace(' ', '', $column)];
        }

        if (is_array($column) && ! isset($column['name'])) {
            $column['name'] = 'anonymous_column_'.Str::random(5);
        }

        $column['name'] = Str::replace(' ', '', $column['name']);

        return $column;
    }

    /**
     * If a column array is missing the "label" attribute, an ugly error would be show.
     * So we add the field Name as a label - it's better than nothing.
     *
     * @param  array  $column  Column definition array.
     * @return array Proper array defining the column.
     */
    protected function makeSureColumnHasLabel($column)
    {
        if (! isset($column['label'])) {
            $column['label'] = mb_ucfirst($this->makeLabel($column['name']));
        }

        return $column;
    }

    /**
     * If a column definition is missing the type, set a default.
     *
     * @param  array  $column  Column definition array.
     * @return array Column definition array with type.
     */
    protected function makeSureColumnHasType($column)
    {
        // Do not alter type if it has been set by developer
        if (isset($column['type'])) {
            return $column;
        }

        // Set text as default column type
        $column['type'] = 'text';

        if (method_exists($this->model, 'translationEnabledForModel') && $this->model->translationEnabledForModel() && array_key_exists($column['name'], $this->model->getTranslations())) {
            return $column;
        }

        $could_be_relation = Arr::get($column, 'entity', false) !== false;

        if ($could_be_relation) {
            $column['type'] = $this->inferFieldTypeFromRelationType($column['relation_type']);
        }

        if (in_array($column['name'], $this->model->getDates())) {
            $column['type'] = 'datetime';
        }

        if ($this->model->hasCast($column['name'])) {
            $attributeType = $this->model->getCasts()[$column['name']];

            switch ($attributeType) {
                case 'array':
                case 'encrypted:array':
                case 'collection':
                case 'encrypted:collection':
                    $column['type'] = 'array';
                    break;
                case 'json':
                case 'object':
                    $column['type'] = 'json';
                    break;
                case 'bool':
                case 'boolean':
                    $column['type'] = 'check';
                    break;
                case 'date':
                    $column['type'] = 'date';
                    break;
                case 'datetime':
                    $column['type'] = 'datetime';
                    break;
                case 'double':
                case 'float':
                case 'int':
                case 'integer':
                case 'real':
                case 'timestamp':
                    $column['type'] = 'number';
                    break;
            }
        }

        return $column;
    }

    /**
     * If a column definition is missing the key, set the default.
     * The key is used when storing all columns using the Settings API,
     * it is used as the "key" of the associative array that holds all columns.
     *
     * @param  array  $column  Column definition array.
     * @return array Column definition array with key.
     */
    protected function makeSureColumnHasKey($column)
    {
        if (! isset($column['key'])) {
            $column['key'] = str_replace('.', '__', $column['name']);
        }

        return $column;
    }

    /**
     * @deprecated Never used. Will be removed in a future version.
     *
     * @param  array  $column  Column definition array.
     * @return array Column definition array with wrapper.
     *
     * @codeCoverageIgnore
     */
    protected function makeSureColumnHasWrapper($column)
    {
        if (! isset($column['wrapper'])) {
            $column['wrapper'] = [];
        }

        return $column;
    }

    protected function makeSureColumnHasEntity($column)
    {
        if (isset($column['entity'])) {
            return $column;
        }

        // if the name is an array it's definitely not a relationship
        if (is_array($column['name'])) {
            return $column;
        }

        // if the name is dot notation it might be a relationship
        if (strpos($column['name'], '.') !== false) {
            $possibleMethodName = Str::before($column['name'], '.');

            // if the first part of the string exists as method in the model
            if (method_exists($this->model, $possibleMethodName)) {
                // check model method for possibility of being a relationship
                $column['entity'] = $this->modelMethodIsRelationship($this->model, $possibleMethodName) ? $column['name'] : false;

                if ($column['entity']) {
                    // if the user setup the attribute in relation string, we are not going to infer that attribute from model
                    // instead we get the defined attribute by the user.
                    if ($this->isAttributeInRelationString($column)) {
                        $column['attribute'] = $column['attribute'] ?? Str::afterLast($column['entity'], '.');
                    }
                }

                return $column;
            }
        }

        // if there's a method on the model with this name
        if (method_exists($this->model, $column['name']) || $this->model->isRelation($column['name'])) {
            // check model method for possibility of being a relationship
            $column['entity'] = $this->modelMethodIsRelationship($this->model, $column['name']);

            return $column;
        }

        // if the name ends with _id and that method exists,
        // we can probably use it as an entity
        if (Str::endsWith($column['name'], '_id')) {
            $possibleMethodName = Str::replaceLast('_id', '', $column['name']);

            if (method_exists($this->model, $possibleMethodName)) {
                // check model method for possibility of being a relationship
                $column['entity'] = $this->modelMethodIsRelationship($this->model, $possibleMethodName);

                return $column;
            }
        }

        return $column;
    }

    /**
     * Infer the attribute for the column when needed.
     *
     * @param  array  $column
     * @return void
     */
    protected function makeSureColumnHasAttribute(array $column)
    {
        return $this->makeSureFieldHasAttribute($column);
    }

    /**
     * If an entity has been defined for the column, but no model,
     * determine the model from that relationship.
     *
     * @param  array  $column  Column definition array.
     * @return array Column definition array with model.
     */
    protected function makeSureColumnHasModel($column)
    {
        // if this is a relation type field and no corresponding model was specified,
        // get it from the relation method defined in the main model
        if (isset($column['entity']) && $column['entity'] !== false && ! isset($column['model'])) {
            $column['model'] = $this->getRelationModel($column['entity']);
        }

        return $column;
    }

    /**
     * If an entity has been defined for the column, but no relation type,
     * determine the relation type from that relationship.
     *
     * @param  array  $column  Column definition array.
     * @return array Column definition array with model.
     */
    protected function makeSureColumnHasRelationType($column)
    {
        if (isset($column['entity']) && $column['entity'] !== false) {
            $column['relation_type'] = $column['relation_type'] ?? $this->inferRelationTypeFromRelationship($column);
        }

        return $column;
    }

    /**
     * Move the most recently added column before or after the given target column. Default is before.
     *
     * @param  string|array  $targetColumn  The target column name or array.
     * @param  bool  $before  If true, the column will be moved before the target column, otherwise it will be moved after it.
     */
    protected function moveColumn($targetColumn, $before = true)
    {
        // TODO: this and the moveField method from the Fields trait should be refactored into a single method and moved
        //       into a common class
        $targetColumnName = is_array($targetColumn) ? $targetColumn['name'] : $targetColumn;
        $columnsArray = $this->columns();

        $targetColumnName = str_replace('.', '__', $targetColumnName);

        if (array_key_exists($targetColumnName, $columnsArray)) {
            $targetColumnPosition = $before ? array_search($targetColumnName, array_keys($columnsArray)) :
                array_search($targetColumnName, array_keys($columnsArray)) + 1;

            $element = array_pop($columnsArray);

            if ($element['priority'] === count($columnsArray)) {
                // the priority was most likely auto-set as it corresponds to the column array count
                // update the priority to the target column position
                $element['priority'] = $targetColumnPosition;
            }

            $beginningPart = array_slice($columnsArray, 0, $targetColumnPosition, true);
            $endingArrayPart = array_slice($columnsArray, $targetColumnPosition, null, true);

            $columnsArray = array_merge($beginningPart, [$element['name'] => $element], $endingArrayPart);
            $this->setOperationSetting('columns', $columnsArray);
        }
    }

    /**
     * Check if the column exists in the database, as a DB column.
     *
     * @param  string  $table
     * @param  string  $name
     * @return bool
     */
    protected function hasDatabaseColumn($table, $name)
    {
        static $cache = [];

        if (! $this->driverIsSql()) {
            return true;
        }

        if (isset($cache[$table])) {
            $columns = $cache[$table];
        } else {
            $columns = $cache[$table] = $this->getSchema()->getColumnListing($table);
        }

        return in_array($name, $columns);
    }

    /**
     * Prepare the column attributes and add it to operation settings.
     */
    private function prepareAttributesAndAddColumn(array|string $column): CrudColumn
    {
        $column = $this->makeSureColumnHasNeededAttributes($column);
        $this->addColumnToOperationSettings($column);

        $column = (new CrudColumn($column['name']))->callRegisteredAttributeMacros();

        return $column;
    }
}
