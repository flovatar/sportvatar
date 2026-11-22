<x-guest-layout>

    <div class="text-center mt-10 mx-auto max-w-screen-xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 xl:mt-28">
        <h2 class="text-4xl tracking-tight leading-10 font-extrabold text-gray-900 sm:text-5xl sm:leading-none md:text-6xl">
            Any questions?
            <br class="xl:hidden">
            <span class="text-indigo-600">Get in touch!</span>
        </h2>
        <p class="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
            We'll be happy to help you out with any general inquiry or specific issue.
        </p>
    </div>


    <div class="py-8 px-4 overflow-hidden sm:px-6 lg:px-8 lg:py-12">
        <div class="relative max-w-xl mx-auto">
            <div class="mt-12">
                <form action="#" method="POST" class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                    <div>
                        <label for="first_name" class="block text-sm font-medium leading-5 text-gray-700">First name</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input id="first_name" class="form-input py-3 px-4 block w-full transition ease-in-out duration-150">
                        </div>
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium leading-5 text-gray-700">Last name</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input id="last_name" class="form-input py-3 px-4 block w-full transition ease-in-out duration-150">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="company" class="block text-sm font-medium leading-5 text-gray-700">Company</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input id="company" class="form-input py-3 px-4 block w-full transition ease-in-out duration-150">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="email" class="block text-sm font-medium leading-5 text-gray-700">Email</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input id="email" type="email" class="form-input py-3 px-4 block w-full transition ease-in-out duration-150">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="phone_number" class="block text-sm font-medium leading-5 text-gray-700">Phone Number</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input id="phone_number" class="form-input py-3 px-4 block w-full transition ease-in-out duration-150" placeholder="+1 (555) 987-6543">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="message" class="block text-sm font-medium leading-5 text-gray-700">Message</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <textarea id="message" rows="4" class="form-textarea py-3 px-4 block w-full transition ease-in-out duration-150"></textarea>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <!--
                                  Simple toggle

                                  On: "bg-indigo-600", Off: "bg-gray-200"
                                -->
                                <span role="checkbox" tabindex="0" aria-checked="false" class="bg-gray-200 relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:shadow-outline" x-on:click="on = !on" @keydown.space.prevent="on = !on" :aria-checked="on.toString()" x-data="{ on: false }" x-description="Simple toggle" x-bind:class="{ 'bg-gray-200': !on, 'bg-indigo-600': on }" >
                <!-- On: "translate-x-5", Off: "translate-x-0" -->
                <span aria-hidden="true" x-bind:class="{ 'translate-x-5': on, 'translate-x-0': !on }" class="translate-x-0 inline-block h-5 w-5 rounded-full bg-white shadow transform transition ease-in-out duration-200"></span>
              </span>
                            </div>
                            <div class="ml-3">
                                <p class="text-base leading-6 text-gray-500">
                                    By selecting this, you agree to the
                                    <a href="/privacy" class="font-medium text-gray-700 underline">Privacy Policy</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
          <span class="w-full inline-flex rounded-md shadow-sm">
            <button type="button" class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base leading-6 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:shadow-outline-indigo active:bg-indigo-700 transition ease-in-out duration-150">
              Let's talk
            </button>
          </span>
                    </div>
                </form>
            </div>
        </div>
    </div>


</x-guest-layout>