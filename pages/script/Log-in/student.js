document.addEventListener("DOMContentLoaded", function() {
    const passField = document.querySelector("#password");
    const showBtn = document.querySelector(".show-btn i");

    showBtn.addEventListener("click", () => {
      if (passField.type === "password") {
        passField.type = "text";
        showBtn.classList.remove("hide-btn");
      } else {
        passField.type = "password";
        showBtn.classList.add("hide-btn");
      }
    });
});

