<?php /* the space in the next line before closing ` >` is needed as a workaround for bug #480 */ ?>
<x-sidebar-card title="Most Active Contributors" alpine_x_data="contributorsData()" >
    <!-- Loading spinner -->
    <div class="row" x-show="loading">
        <div class="col-12">
            <div class="d-flex justify-content-center">
                <div class="spinner-border icon-style" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contributors list -->
    <div class="row" x-show="!loading">
        <div class="col-12">
            <ul class="list-unstyled">
                <template x-for="contributor in contributors" :key="contributor.id">
                    <li>
                        <a :href="contributor.html_url" target="_blank">
                            <img :src="contributor.avatar_url" :alt="contributor.login" width="32" height="32" class="rounded-circle my-1 me-2">
                            <span x-text="contributor.login"></span>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
        <div class="col">
            <x-button-link href="https://github.com/lonnieezell/Bonfire2/graphs/contributors" color="secondary" >
                View All Contributors
            </x-button-link>
        </div>
    </div>
</x-sidebar-card>