@extends('statamic::layout')
@section('title', Statamic::crumb(__('Backup VCS'), __('Utilities')))
@section('wrapper_class', 'max-w-full')

@section('content')

    <header class="mb-3">
                @include('statamic::partials.breadcrumb', [
                        'url' => cp_route('utilities.index'),
                        'title' => __('Utilities')
                    ])
        <h1 class="mb-2">{{ __('Backup VCS') }}</h1>
        <h3>Welcome to Version Control System</h3>
        <br>
        <p>This page is here to arrange the content of the website.</p>
        <br>
        <p>This utility arranges things like:</p>
        <ul class="list-disc ml-3">
            <li>Updating the content version on production,</li>
            <li>Reverting the content to specific previous version,</li>
            <li>Monitoring the changes applied to the content</li>
        </ul>
    </header>

    <script>
        function revertBackInTime( backupID) {
            location.href=`${location.href}?rebase-to-backup-id=${backupID}`
        }
        if (location.href.includes('?rebase-to-backup-id')) location.href = location.href.split('?')[0];
    </script>

{{--    {{dd($result)}}--}}


    <div class="card p-0">
        <div class="w-full overflow-auto">
            <table class="data-table">
                <thead class="pb-1">
                <th>Date - Author - Change</th>
                <th>Revert to this state</th>
                </thead>
                <tbody>
                @foreach ($backups as $key => $backup)
                    <tr>
                        <td style="min-width: 120px; vertical-align: center;">{{ $backup['name']}}</td>
                        <td>
                            @if($key !== 0) <div
                                class="px-1 py-1 bg-sky-200 rounded-lg text-center cursor-pointer text-black"
                                style="background-color: rgb(186 230 253);"
                                x-on:click="() => {
                                    if (confirm('Do you really want to revert all content changes to the point of this state: '
                                    + '{{$backup['name']}} ?')) revertBackInTime({{ $key }});
                                }">Revert to here</div>
                            @else
                                <div
                                    class="px-1 py-1 bg-sky-200 rounded-lg text-center text-black"
                                    style="background-color: rgb(186 230 253);">It's the latest version :)</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

@stop
