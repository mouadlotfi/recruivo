@foreach($jobs as $job)
    <div class="contents" data-infinite-item>
        <x-job-card :job="$job" />
    </div>
@endforeach
