</main>

<footer class="bg-gray-800 text-white mt-12">
    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <i class="fas fa-building text-primary text-2xl"></i>
                    <span class="text-xl font-bold">KenyaRentals</span>
                </div>
                <p class="text-gray-400">Find your perfect space across Kenya. Office spaces, commercial properties, gardens, and more.</p>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="<?= $base_url ?>/" class="hover:text-white transition duration-300">Home</a></li>
                    <li><a href="<?= $base_url ?>/dashboard/tenant/search.php" class="hover:text-white transition duration-300">Search Properties</a></li>
                    <li><a href="<?= $base_url ?>/auth/register.php" class="hover:text-white transition duration-300">Become a Landlord</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Cities</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="#" class="hover:text-white transition duration-300">Nairobi</a></li>
                    <li><a href="#" class="hover:text-white transition duration-300">Mombasa</a></li>
                    <li><a href="#" class="hover:text-white transition duration-300">Kisumu</a></li>
                    <li><a href="#" class="hover:text-white transition duration-300">Nakuru</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Contact</h4>
                <ul class="space-y-2 text-gray-400">
                    <li class="flex items-center">
                        <i class="fas fa-phone mr-2"></i>
                        +254 700 000 000
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-envelope mr-2"></i>
                        info@kenyarentals.co.ke
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        Nairobi, Kenya
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400">
            <p>&copy; <?= date('Y') ?> KenyaRentals. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="<?= $base_url ?>/assets/js/app.js"></script>
</body>
</html>