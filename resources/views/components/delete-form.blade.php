@props(['action', 'confirm' => 'Yakin ingin menghapus data ini?'])

<form method="POST" action="{{ $action }}" x-data
      @submit.prevent="if (confirm('{{ $confirm }}')) $el.submit()"
      class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="rounded-md p-1.5 text-red-500 transition hover:bg-red-50" title="Hapus">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </button>
</form>
