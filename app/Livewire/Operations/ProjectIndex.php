<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Enums\PermissionEnum;
use App\Services\OperationDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dự án & Khách hàng')]
final class ProjectIndex extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $branch = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    #[Url(except: 'cards')]
    public string $view = 'cards';

    public function mount(): void
    {
        Gate::authorize(PermissionEnum::ProjectView->value);

        if (! in_array($this->view, ['cards', 'table'], true)) {
            $this->view = 'cards';
        }
    }

    public function setView(string $view): void
    {
        if (! in_array($view, ['cards', 'table'], true)) {
            return;
        }

        $this->view = $view;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->branch = '';
        $this->statusFilter = '';
        $this->typeFilter = '';
    }

    #[On('project:saved')]
    public function refreshProjects(): void
    {
        // The listener is intentionally empty; receiving the event re-renders the page.
    }

    public function render(OperationDataService $operations): View
    {
        $data = $operations->all();

        return view('operations.projects', $data + [
            'filteredProjects' => $this->filterProjects($data['projects']),
            'statusOptions' => $data['status'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @return array<int, array<string, mixed>>
     */
    private function filterProjects(array $projects): array
    {
        $query = mb_strtolower(trim($this->search));

        return array_values(array_filter($projects, function (array $project) use ($query): bool {
            if ($this->branch !== '' && $project['branch'] !== $this->branch) {
                return false;
            }

            if ($this->statusFilter !== '' && $project['status'] !== $this->statusFilter) {
                return false;
            }

            if ($this->typeFilter !== '' && $project['customer_type'] !== $this->typeFilter) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            $haystack = mb_strtolower($project['name'].' '.$project['customer'].' '.$project['code'].' '.$project['manager_name']);

            return str_contains($haystack, $query);
        }));
    }
}
