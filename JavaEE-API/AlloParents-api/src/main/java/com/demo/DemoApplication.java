package com.demo;
 
import jakarta.ws.rs.ApplicationPath; // import de @ApplicationPath
import jakarta.ws.rs.core.Application; // import de Application
 
// Cette classe est le point d'entrée de notre application Jakarta EE
@ApplicationPath("/api")
public class DemoApplication extends Application {
    // Pas besoin de code ici, Jakarta EE gère tout pour nous
}