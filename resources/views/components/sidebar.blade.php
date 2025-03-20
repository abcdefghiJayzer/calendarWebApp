<aside class="fixed top-0 left-0 h-full w-64 bg-green-800 border-r">
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-center h-16">
            <a href="/" class="text-xl font-semibold text-white">
                kalendaryo
            </a>
        </div>

        <div class="flex-grow p-4 space-y-2">
            <button onclick="openModal()"
                class="block w-full py-2 px-4 text-white bg-green-900 rounded-lg hover:bg-green-700">
                Create Event
            </button>

            <a href="{{ route('home') }}"
                class="block py-2 px-4 text-white bg-green-900 rounded-lg hover:text-white">
                Home
            </a>
        </div>
    </div>
</aside>
