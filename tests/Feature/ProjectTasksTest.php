<?php

namespace Tests\Feature;

use App\Project;
use Tests\TestCase;
use Tests\Setup\ProjectFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectTasksTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    public function guestsCannotManageProjects()
    {
        $project = factory('App\Project')->create();
        $this->post($project->path() .'/tasks')->assertRedirect('login');
    }

    /** @test */
    public function onlyTheOwnerCanAddTasksToProject()
    {
        $this->signIn();

        $project = factory('App\Project')->create();
        $this->post($project->path() .'/tasks', ['body' => 'changed'])->assertStatus(403);
        $this->assertDatabaseMissing('tasks', ['body' => 'changed']);
    }

    /** @test */
    public function onlyTheOwnerCanUpdateTasks()
    {
        $this->signIn();

        $project = factory('App\Project')->create();
        $task = $project->addTask('test task');

        $this->patch($task->path(), ['body' => 'changed'])->assertStatus(403);
        $this->assertDatabaseMissing('tasks', ['body' => 'changed']);
    }

    /** @test */
    public function projectCanHaveTasks()
    {
        $this->signIn();

        $project = auth()->user()->projects()->create(
            factory('App\Project')->raw()
        );
        //or
        //$project = factory('App\Project')->create(['owner_id' => auth()->id()]);

        $this->post($project->path() .'/tasks', ['body' => 'changed']);
        $this->get($project->path())->assertSee('changed');
    }

    /** @test */
    public function tasksCanbeUpdated()
    {
        $project = app(ProjectFactory::class)
            ->ownedBy($this->signIn())
            ->withTasks(1)
            ->create();

        $this->patch($project->tasks->first()->path(), [
            'body' => 'changed',
            'completed' => true
        ]);

        // or
        // $project = app(ProjectFactory::class)
        //     ->withTasks(1)
        //     ->create();
        // $this->actingAs($project->owner)
        //     ->patch($project->tasks[0]->path(), [
        //     'body' => 'changed',
        //     'completed' => true
        // ]);

        $this->assertDatabaseHas('tasks', [
            'body' => 'changed',
            'completed' => true
        ]);
    }

    /** @test */
    public function projectsRequireBody()
    {
        $this->signIn();

        $project = auth()->user()->projects()->create(
            factory('App\Project')->raw()
        );

        $attributes = factory('App\Task')->raw(['body' => '']);

        $this->post($project->path().'/tasks', $attributes)->assertSessionHasErrors('body');
    }
}
