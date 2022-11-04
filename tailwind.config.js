const colors = require("tailwindcss/colors");

module.exports = {
  content: ["./**/*.tpl", "./assets/**/*.js"],
  prefix: "ojt-",
  darkMode: "class",
  // important: "#ojt-plugin",
  theme: {
    extend: {
      colors: {
        primary: colors.purple,
        danger: colors.rose,
        success: colors.green,
        warning: colors.yellow,
      },
    },
  },
  plugins: [require("@tailwindcss/forms")],
};
