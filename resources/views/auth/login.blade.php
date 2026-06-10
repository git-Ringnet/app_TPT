<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div x-data="{ tab: 'login', showSuccessModal: false }" @register-success.window="showSuccessModal = true; tab = 'login'">
        <!-- Login Form -->
        <div x-show="tab === 'login'">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                        required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label for="password" :value="__('Password')" />

                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                        autocomplete="current-password" />

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            name="remember">
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-between mt-6">
                    @if (Route::has('password.request'))
                        <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                            href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif

                    <x-primary-button class="ms-3">
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>

                <!-- Toggle Link -->
                <div
                    class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700/50 text-center text-sm text-gray-600 dark:text-gray-400">
                    Chưa có tài khoản?
                    <a href="#" @click.prevent="tab = 'register'"
                        class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 underline focus:outline-none">
                        Đăng ký tài khoản Test tại đây
                    </a>
                </div>
            </form>
        </div>

        <!-- Register Form -->
        <div x-show="tab === 'register'" style="display: none;">
            <form id="register-form" class="space-y-4">
                <!-- Name (Username) -->
                <div>
                    <x-input-label for="register_username" :value="__('Username')" />
                    <x-text-input id="register_username" class="block mt-1 w-full" type="text" name="username" required
                        autocomplete="name" placeholder="Nhập tên đăng nhập" />
                </div>

                <!-- Email Address -->
                <div class="mt-4">
                    <x-input-label for="register_email" :value="__('Email')" />
                    <x-text-input id="register_email" class="block mt-1 w-full" type="email" name="email" required
                        autocomplete="username" placeholder="Nhập địa chỉ email" />
                </div>

                <!-- Phone Number -->
                <div class="mt-4">
                    <x-input-label for="register_phone" :value="__('Phone')" />
                    <x-text-input id="register_phone" class="block mt-1 w-full" type="tel" name="phone" required
                        autocomplete="tel" placeholder="Nhập số điện thoại" />
                </div>

                <div class="flex items-center justify-end mt-6">
                    <x-primary-button class="w-full justify-center py-2.5">
                        Đăng ký
                    </x-primary-button>
                </div>

                <!-- Toggle Link -->
                <div
                    class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700/50 text-center text-sm text-gray-600 dark:text-gray-400">
                    Đã có tài khoản?
                    <a href="#" id="toggle-to-login" @click.prevent="tab = 'login'"
                        class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 underline focus:outline-none">
                        Đăng nhập tại đây
                    </a>
                </div>
            </form>
        </div>

        <!-- Success Modal -->
        <div x-show="showSuccessModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
             
             <!-- Backdrop -->
             <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

             <!-- Modal Container -->
             <div class="flex min-h-full items-center justify-center p-4 text-center">
                 <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left shadow-2xl transition-all w-full max-w-md"
                      x-transition:enter="transition ease-out duration-300"
                      x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                      x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                      x-transition:leave="transition ease-in duration-200"
                      x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                      x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                      
                      <!-- Success Icon -->
                      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                          <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                          </svg>
                      </div>

                      <!-- Content -->
                      <div class="text-center">
                          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Đăng ký thành công!</h3>
                          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Thông tin đăng ký của bạn đã được ghi nhận. Vui lòng dùng tài khoản demo dưới đây để trải nghiệm:</p>
                          
                          <!-- Credentials Box -->
                          <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800 rounded-xl p-4 mb-5 text-left space-y-2">
                              <div class="flex justify-between text-sm">
                                  <span class="text-gray-500 dark:text-gray-400 font-medium">Email đăng nhập:</span>
                                  <span class="text-gray-900 dark:text-white font-bold">admin@demo.com</span>
                              </div>
                              <div class="flex justify-between text-sm">
                                  <span class="text-gray-500 dark:text-gray-400 font-medium">Mật khẩu:</span>
                                  <span class="text-gray-900 dark:text-white font-bold">123456</span>
                              </div>
                              <div class="flex justify-between text-sm border-t border-gray-200 dark:border-gray-700/50 pt-2">
                                  <span class="text-gray-500 dark:text-gray-400 font-medium">Vai trò:</span>
                                  <span class="text-indigo-600 dark:text-indigo-400 font-semibold">Admin</span>
                              </div>
                          </div>

                          <p class="text-xs text-gray-500 dark:text-gray-400 italic mb-6 text-center">
                              *(Hệ thống có sẵn các tài khoản demo khác: <strong class="text-gray-700 dark:text-gray-300 font-semibold">ketoan@demo.com</strong>, <strong class="text-gray-700 dark:text-gray-300 font-semibold">quankho@demo.com</strong>, <strong class="text-gray-700 dark:text-gray-300 font-semibold">baohanh@demo.com</strong> với mật khẩu <strong class="text-gray-700 dark:text-gray-300 font-semibold">123456</strong>)
                          </p>
                      </div>

                      <!-- Button -->
                      <div>
                          <button type="button" 
                                  @click="showSuccessModal = false"
                                  class="inline-flex w-full justify-center rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-3 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all">
                              Trải nghiệm ngay
                          </button>
                      </div>
                 </div>
             </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const CONFIG = {
                hubUrl: "https://portal.app.ringnet.vn/api/demo-register",
                siteName: "DemoApp"
            };

            const registerForm = document.getElementById("register-form");
            if (!registerForm) return;

            registerForm.addEventListener("submit", async (e) => {
                // 1. Ngăn chặn hành vi gửi form tải lại trang ban đầu
                e.preventDefault();

                const usernameInput = registerForm.querySelector('input[name="username"]') || registerForm.querySelector('#register_username') || registerForm.querySelector('input[name="name"]');
                const emailInput = registerForm.querySelector('input[name="email"]') || registerForm.querySelector('#register_email');
                const phoneInput = registerForm.querySelector('input[name="phone"]') || registerForm.querySelector('input[name="tel"]') || registerForm.querySelector('#register_phone');
                const submitButton = registerForm.querySelector('button[type="submit"]') || registerForm.querySelector('input[type="submit"]');

                if (!usernameInput || !emailInput || !phoneInput) {
                    alert("Không tìm thấy các trường thông tin đăng ký (Username, Email, Phone) trong form.");
                    return;
                }

                const payload = {
                    username: usernameInput.value.trim(),
                    email: emailInput.value.trim(),
                    phone: phoneInput.value.trim(),
                    site_name: CONFIG.siteName
                };

                const originalButtonHtml = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = "Đang xử lý...";

                try {
                    // 2. Gửi thông tin đăng ký lên Hub cha để ghi nhận log quan tâm
                    const response = await fetch(CONFIG.hubUrl, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (response.ok) {
                        // 3. Tự động điền tài khoản demo vào form đăng nhập
                        const loginEmailInput = document.getElementById("email");
                        const loginPasswordInput = document.getElementById("password");
                        if (loginEmailInput) loginEmailInput.value = "admin@demo.com";
                        if (loginPasswordInput) loginPasswordInput.value = "123456";

                        // 4. Reset form đăng ký
                        registerForm.reset();
                        
                        // 5. Gửi event báo thành công để AlpineJS bật modal và chuyển tab đăng nhập
                        window.dispatchEvent(new CustomEvent('register-success'));
                    } else {
                        alert(`Lỗi: ${result.message || "Đăng ký thất bại"}`);
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHtml;
                    }
                } catch (error) {
                    console.error(error);
                    alert("Không thể kết nối đến hệ thống Hub trung tâm.");
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHtml;
                }
            });
        });
    </script>
</x-guest-layout>