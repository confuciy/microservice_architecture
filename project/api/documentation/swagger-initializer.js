window.onload = function() {
  //<editor-fold desc="Changeable Configuration Block">

  // the following lines will be replaced by docker/configurator, when it runs in a docker-container
  window.ui = SwaggerUIBundle({
    url: "http://arch.homework/api/documentation/api.json",
    dom_id: '#swagger-ui',
    deepLinking: true,
    enableCORS: false,
    basePath: "http://arch.homework",
    displayOperationId: false,
    // request.curlOptions: [
    //   "-g",
    //   "--limit-rate 20k"
    // ],
    supportedSubmitMethods: ["get", "put", "post", "delete", "options", "head", "patch", "trace"],
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    plugins: [
      SwaggerUIBundle.plugins.DownloadUrl
    ],
    layout: "BaseLayout"
  });

  //BaseLayout StandaloneLayout
  //</editor-fold>
};
