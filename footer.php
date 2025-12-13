</main>
        <!-- MAIN SCROLLABLE CONTENT END -->

        <!-- BOTTOM NAVIGATION BAR (Sticky) -->
        <nav class="bg-white border-t border-gray-200 px-4 py-3 flex justify-between items-end sticky bottom-0 z-50 w-full sm:rounded-b-[2rem]">
            
            <!-- 1. Beranda -->
            <a href="<?php echo home_url(); ?>" class="flex flex-col items-center gap-1 <?php echo is_front_page() ? 'text-emerald-600' : 'text-gray-400 hover:text-emerald-600'; ?> transition w-16">
                <i class="fas fa-home text-lg"></i>
                <span class="text-[10px] font-medium">Beranda</span>
            </a>
            
            <!-- 2. Jelajah (Archive Desa) -->
            <a href="<?php echo post_type_archive_title('', false) == 'Desa' ? '#' : home_url('/desa'); ?>" class="flex flex-col items-center gap-1 text-gray-400 hover:text-emerald-600 transition w-16">
                <i class="fas fa-compass text-lg"></i>
                <span class="text-[10px] font-medium">Jelajah</span>
            </a>
            
            <!-- 3. Produk (Center Featured) -->
            <div class="relative -top-5 w-16 flex flex-col items-center">
                <a href="<?php echo home_url('/produk'); ?>" class="bg-emerald-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-lg border-4 border-gray-50 transform hover:scale-105 transition">
                    <i class="fas fa-box-open text-xl"></i>
                </a>
                <span class="text-[10px] font-medium text-emerald-700 mt-1">Produk</span>
            </div>
            
            <!-- 4. Transaksi -->
            <a href="<?php echo home_url('/transaksi'); ?>" class="flex flex-col items-center gap-1 text-gray-400 hover:text-emerald-600 transition w-16">
                <i class="fas fa-receipt text-lg"></i>
                <span class="text-[10px] font-medium">Transaksi</span>
            </a>
            
            <!-- 5. Akun -->
            <a href="<?php echo is_user_logged_in() ? home_url('/akun-saya') : home_url('/login'); ?>" class="flex flex-col items-center gap-1 text-gray-400 hover:text-emerald-600 transition w-16">
                <i class="fas fa-user text-lg"></i>
                <span class="text-[10px] font-medium">Akun</span>
            </a>
        </nav>

    </div> <!-- End of Mobile Wrapper -->

    <?php wp_footer(); ?>
</body>
</html>