<?php namespace Void\Comic\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateComicsTable extends Migration
{

    public function up()
    {
        Schema::create('comics', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('match_id')->unsigned()->nullable()->index();
            $table->string('winner')->nullable();
            $table->integer('points')->nullable();
            $table->boolean('is_accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->boolean('is_extended')->default(false);
            $table->timestamp('extended_at')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('comics');
    }

}
