<?php

namespace Tests\Browser\QueryString;

use Livewire\Livewire;
use Laravel\Dusk\Browser;
use Tests\Browser\TestCase;

class Test extends TestCase
{
    public function test_core_query_string_pushstate_logic()
    {
        $this->browse(function (Browser $browser) {
            Livewire::visit($browser, Component::class, '?foo=baz&eoo=lob')
                /*
                 * Check that the intial property value is set from the query string.
                 */
                ->assertSeeIn('@output', 'baz')
                ->assertInputValue('@input', 'baz')

                /*
                 * Check that Livewire doesn't mess with query string order.
                 */
                ->assertScript('return !! window.location.search.match(/foo=baz&eoo=lob/)')
                ->assertSeeIn('@output', 'baz')
                ->assertInputValue('@input', 'baz')

                /**
                 * Change a property and see it reflected in the query string.
                 */
                ->waitForLivewire()->type('@input', 'bob')
                ->assertSeeIn('@output', 'bob')
                ->assertInputValue('@input', 'bob')
                ->assertQueryStringHas('foo', 'bob')

                /**
                 * Hit the back button and see the change reflected in both
                 * the query string AND the actual property value.
                 */
                ->back()
                ->assertSeeIn('@output', 'baz')
                ->assertQueryStringHas('foo', 'baz')

                /**
                 * Setting a property to a value marked as "except"
                 * removes the property entirely from the query string.
                 */
                ->assertSeeIn('@bar-output', 'baz')
                ->assertQueryStringHas('bar')
                ->waitForLivewire()->type('@bar-input', 'except-value')
                ->assertQueryStringMissing('bar')

                /**
                 * Add a nested component on the page and make sure
                 * both components play nice with each other.
                 */
                ->assertQueryStringMissing('baz')
                ->waitForLivewire()->click('@show-nested')
                ->pause(25)
                ->assertQueryStringHas('baz', 'bop')
                ->assertSeeIn('@baz-output', 'bop')
                ->waitForLivewire()->type('@baz-input', 'lop')
                ->assertQueryStringHas('baz', 'lop')
                ->waitForLivewire()->type('@input', 'plop')
                ->waitForLivewire()->type('@baz-input', 'ploop')
                ->assertQueryStringHas('foo', 'plop')
                ->assertQueryStringHas('baz', 'ploop')
                ->back()
                ->back()
                ->assertQueryStringHas('baz', 'lop')

                /**
                 * Can change an array property.
                 */
                ->assertSeeIn('@bob.output', '["foo","bar"]')
                ->waitForLivewire()->click('@bob.modify')
                ->assertSeeIn('@bob.output', '["foo","bar","baz"]')
                ->refresh()
                ->assertSeeIn('@bob.output', '["foo","bar","baz"]')
            ;
        });
    }

    public function test_back_button_after_refresh_works_with_nested_components()
    {
        $this->browse(function (Browser $browser) {
            Livewire::visit($browser, Component::class)
                ->waitForLivewire()->click('@show-nested')
                ->waitForLivewire()->type('@baz-input', 'foo')
                ->assertSeeIn('@baz-output', 'foo')
                ->refresh()
                ->back()
                ->forward()
                ->assertSeeIn('@baz-output', 'foo')

                // Interact with the page again to make sure everything still works.
                ->waitForLivewire()->type('@baz-input', 'bar')
                ->assertSeeIn('@baz-output', 'bar')
            ;
        });
    }

    public function test_excepts_results_in_no_query_string()
    {
        $this->browse(function (Browser $browser) {
            Livewire::visit($browser, ComponentWithExcepts::class)
                ->assertSeeIn('@output', 'On page 1');

            $this->assertStringNotContainsString('?', $browser->driver->getCurrentURL());

            Livewire::visit($browser, ComponentWithExcepts::class, '?page=1')
                ->assertSeeIn('@output', 'On page 1');

            $this->assertStringNotContainsString('?', $browser->driver->getCurrentURL());

            Livewire::visit($browser, ComponentWithExcepts::class, '?page=2')
                ->assertSeeIn('@output', 'On page 2')
                ->assertQueryStringHas('page', 2);
        });
    }
}
