<?php

namespace Backpack\Generators\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class CrudFormOperationBackpackCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'backpack:crud-form-operation';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backpack:crud-form-operation {name} {--no-id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an operation trait with a Backpack form';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Trait';

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace($this->laravel->getNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'Operation.php';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../stubs/crud-form-operation.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers\Admin\Operations';
    }

    /**
     * Replace the table name for the given stub.
     */
    protected function replaceNameStrings(string &$stub, string $name): self
    {
        $name = Str::of($name)->afterLast('\\');

        $stub = str_replace('DummyClass', $name->studly(), $stub);
        $stub = str_replace('dummyClass', $name->lcfirst(), $stub);
        $stub = str_replace('Dummy Class', $name->snake()->replace('_', ' ')->title(), $stub);
        $stub = str_replace('dummy-class', $name->snake('-'), $stub);
        $stub = str_replace('dummy_class', $name->snake(), $stub);
        $stub = str_replace('DUMMY_ROUTE_WITH_ID', $this->option('no-id') ? 'false' : 'true', $stub);
        $stub = str_replace('DUMMY_BUTTON_STACK', $this->option('no-id') ? 'top' : 'line', $stub);
        $stub = str_replace('DUMMY_FORM_ACTION_CALLBACK', $this->option('no-id') ? 'formLogic: function ($inputs, $entry) {' : 'id: $id, formLogic: function ($inputs, $entry) {', $stub);
        $stub = str_replace('DUMMY_FUNCTION_PARAMETERS', $this->option('no-id') ? '' : 'int $id', $stub);
        $stub = str_replace('DUMMY_GETFORM_VIEW_PARAMETER', $this->option('no-id') ? '' : '$id', $stub);

        return $this;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this
            ->replaceNamespace($stub, $name)
            ->replaceNameStrings($stub, $name)
            ->replaceClass($stub, $name);
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return Str::of($this->argument('name'))
            ->trim()
            ->studly();
    }
}
