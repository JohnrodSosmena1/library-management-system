@extends('layouts.app')

@section('title', 'Categories')
@section('page-title', 'Categories')
@section('page-sub', 'Manage book categories')

@section('content')
<div class="dash-grid">

    {{-- Category List --}}
    <div class="panel">
        <div class="panel-head">
            <div>
                <div class="panel-title">All Categories</div>
                <div class="panel-sub">{{ $categories->count() }} categories</div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('add-cat-form').classList.toggle('hidden')">
                ＋ Add Category
            </button>
        </div>

        {{-- Inline Add Form --}}
        <div id="add-cat-form" class="hidden" style="padding:16px 20px;border-bottom:1px solid var(--border)">
            <form method="POST" action="{{ route('categories.store') }}" style="display:flex;gap:10px">
                @csrf
                <input type="text" name="name" placeholder="Category name e.g. Biography"
                       value="{{ old('name') }}" style="flex:1">
                <button type="submit" class="btn btn-primary">Add</button>
            </form>
            @error('name')<div class="field-error" style="padding:4px 0">{{ $message }}</div>@enderror
        </div>

        <table>
            <thead>
                <tr>
                    <th>Category ID</th>
                    <th>Name</th>
                    <th>Books</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $cat)
                    <tr>
                        <td class="mono">CAT-{{ str_pad($cat->id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td class="bold">{{ $cat->name }}</td>
                        <td>{{ $cat->books_count }}</td>
                        <td>
                            <form method="POST" action="{{ route('categories.destroy', $cat) }}"
                                  onsubmit="return confirm('Delete category \'{{ addslashes($cat->name) }}\'?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"
                                    {{ $cat->books_count > 0 ? 'disabled title=Has books assigned' : '' }}>
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Bar Chart --}}
    <div class="panel">
        <div class="panel-head">
            <div class="panel-title">Books by Category</div>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
            @php $total = $categories->sum('books_count'); @endphp
            @foreach($categories as $cat)
                @php $pct = $total > 0 ? round($cat->books_count / $total * 100) : 0; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
                        <span>{{ $cat->name }}</span>
                        <span class="muted">{{ $cat->books_count }} books</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-open form if there's an error
    @if($errors->any())
        document.getElementById('add-cat-form').classList.remove('hidden');
    @endif
</script>
@endpush