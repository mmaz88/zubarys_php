module.exports = {
  plugins: {
    "postcss-import": {},
    "postcss-preset-env": {
      // Enable modern CSS features, including nesting
      features: {
        "nesting-rules": true,
      },
    },
  },
};
