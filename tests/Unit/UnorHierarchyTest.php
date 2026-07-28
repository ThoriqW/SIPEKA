<?php

namespace Tests\Unit;

use App\Models\Unor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnorHierarchyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_unor_without_parent()
    {
        $unor = Unor::create([
            'nama_unor' => 'Root UNOR',
            'kode_unor' => 'ROOT',
            'parent_id' => null,
        ]);

        $this->assertNotNull($unor->id);
        $this->assertNull($unor->parent_id);
    }

    #[Test]
    public function it_allows_unor_with_parent()
    {
        $root = Unor::create(['nama_unor' => 'Root', 'kode_unor' => 'ROOT', 'parent_id' => null]);
        $child = Unor::create(['nama_unor' => 'Child', 'kode_unor' => 'CHILD', 'parent_id' => $root->id]);

        $this->assertEquals($root->id, $child->parent_id);
        $this->assertEquals('Root', $child->parent->nama_unor);
    }

    #[Test]
    public function it_has_children_relation()
    {
        $root = Unor::create(['nama_unor' => 'Root', 'kode_unor' => 'ROOT', 'parent_id' => null]);
        Unor::create(['nama_unor' => 'Child 1', 'kode_unor' => 'C1', 'parent_id' => $root->id]);
        Unor::create(['nama_unor' => 'Child 2', 'kode_unor' => 'C2', 'parent_id' => $root->id]);

        $this->assertCount(2, $root->children);
    }

    #[Test]
    public function it_can_build_multi_level_hierarchy()
    {
        $level0 = Unor::create(['nama_unor' => 'Level 0', 'kode_unor' => 'L0', 'parent_id' => null]);
        $level1 = Unor::create(['nama_unor' => 'Level 1', 'kode_unor' => 'L1', 'parent_id' => $level0->id]);
        $level2 = Unor::create(['nama_unor' => 'Level 2', 'kode_unor' => 'L2', 'parent_id' => $level1->id]);

        $this->assertEquals($level0->id, $level1->parent_id);
        $this->assertEquals($level1->id, $level2->parent_id);
        $this->assertEquals($level0->id, $level2->parent->parent_id);
    }

    #[Test]
    public function it_has_singkatan_field()
    {
        $unor = Unor::create([
            'nama_unor' => 'Dinas Test',
            'kode_unor' => 'DT',
            'singkatan' => 'Dintest',
            'parent_id' => null,
        ]);

        $this->assertEquals('Dintest', $unor->singkatan);
    }
}
