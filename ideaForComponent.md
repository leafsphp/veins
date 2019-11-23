So basically, we're going with smth Vue-ish

We're going to have a leaf veins plugin which will be like the component we can extend

```php
use Leaf\Veins\Component;

class Counter extends Component
{
    $this->state = [
		"count" => 0
	];

    public function increment()
    {
        $this->state->count++;
    }

    public function decrement()
    {
        $this->state->count--;
    }

    public function render()
    {
		$this->set([ "count", $this->state->count ]);
        $this->renderTemplate('views/counter');
    }
}
```