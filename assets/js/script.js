
// Fitur pencarian buku
const searchInput = document.getElementById("searchInput");

if (searchInput) {
    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();
        const books = document.querySelectorAll(".book-card");

        books.forEach(function (book) {
            const title = book.querySelector("h3").textContent.toLowerCase();
            const author = book.querySelector(".author").textContent.toLowerCase();

            if (title.includes(keyword) || author.includes(keyword)) {
                book.style.display = "";
            } else {
                book.style.display = "none";
            }
        });
    });
}

// Simulasi tombol peminjaman
function borrowBook(title) {
    alert("Kamu memilih buku: " + title + "\nFitur peminjaman akan segera diproses.");
}
