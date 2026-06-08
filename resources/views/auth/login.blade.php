<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div x-data="{ tab: 'login' }">
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
                    <x-input-label for="username" :value="__('Username')" />
                    <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" required
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
                    <x-input-label for="phone" :value="__('Phone')" />
                    <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" required
                        autocomplete="tel" placeholder="Nhập số điện thoại" />
                </div>

                <div class="flex items-center justify-end mt-6">
                    <x-primary-button class="w-full justify-center py-2.5">
                        Đăng ký
                    </x-primary-button>
                </div>

                <!-- Toggle Link -->
                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700/50 text-center text-sm text-gray-600 dark:text-gray-400">
                    Đã có tài khoản?
                    <a href="#" id="toggle-to-login" @click.prevent="tab = 'login'"
                        class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 underline focus:outline-none">
                        Đăng nhập tại đây
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const CONFIG = {
            hubUrl: "http://127.0.0.1:8001/api/demo-register",
            siteName: "Thiên Phát Tiến"
        };

        const registerForm = document.getElementById("register-form");
        if (!registerForm) return;

        registerForm.addEventListener("submit", async (e) => {
            // 1. Ngăn chặn hành vi gửi form tải lại trang ban đầu
            e.preventDefault();

            const usernameInput = registerForm.querySelector('input[name="username"]') || registerForm.querySelector('#username') || registerForm.querySelector('input[name="name"]');
            const emailInput = registerForm.querySelector('input[name="email"]') || registerForm.querySelector('#register_email');
            const phoneInput = registerForm.querySelector('input[name="phone"]') || registerForm.querySelector('input[name="tel"]') || registerForm.querySelector('#phone');
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
                    // 3. Hiển thị tài khoản dùng thử mẫu từ seeder cho khách hàng
                    alert(`Đăng ký thông tin thành công!\n\nVui lòng sử dụng tài khoản dùng thử sau để trải nghiệm hệ thống:\n- Email: admin@demo.com\n- Mật khẩu: 123456\n- Vai trò: Admin\n\n(Hệ thống có sẵn các tài khoản demo khác: ketoan@demo.com, quankho@demo.com, baohanh@demo.com với mật khẩu 123456)`);

                    // 4. Tự động điền tài khoản demo vào form đăng nhập
                    const loginEmailInput = document.getElementById("email");
                    const loginPasswordInput = document.getElementById("password");
                    if (loginEmailInput) loginEmailInput.value = "admin@demo.com";
                    if (loginPasswordInput) loginPasswordInput.value = "123456";

                    // 5. Reset form đăng ký và chuyển về tab đăng nhập
                    registerForm.reset();
                    const toggleToLogin = document.getElementById("toggle-to-login");
                    if (toggleToLogin) {
                        toggleToLogin.click();
                    }
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
