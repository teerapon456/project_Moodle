# Moodle API Connection Test

## Quick Test

1. **Update credentials** in `test-moodle-connection.ts`:
   ```typescript
   const TEST_USERNAME = 'your_username'; // Replace with actual username
   const TEST_PASSWORD = 'your_password'; // Replace with actual password  
   const SERVICE_NAME = 'your_service_name'; // Replace with your service name
   ```

2. **Run the test**:
   ```bash
   # From the frontend directory
   npx tsx test-moodle-connection.ts
   ```

   Or if tsx is not available:
   ```bash
   npx ts-node test-moodle-connection.ts
   ```

## What the Test Does

✅ **Step 1**: Creates Moodle API client  
✅ **Step 2**: Tests login and gets authentication token  
✅ **Step 3**: Creates authenticated client with token  
✅ **Step 4**: Retrieves site information  
✅ **Step 5**: Lists available courses  

## Expected Output

```
🔍 Testing Moodle API Connection...
📡 Moodle URL: http://localhost:8080
✅ Moodle API client created successfully

🔑 Testing login...
✅ Login successful!
🎫 Token: abc123...

✅ Authenticated client created

📊 Testing site info...
✅ Site info retrieved successfully!
🏫 Site Name: Your Moodle Site
👤 User: Admin User
🆔 User ID: 2
📋 Available Functions: 45

🔧 Available Functions (first 5):
  1. core_course_get_courses (v2024041500)
  2. core_user_get_users (v2024041500)
  3. core_webservice_get_site_info (v2024041500)
  ...

📚 Testing course retrieval...
✅ Found 3 courses

📖 First 3 courses:
  1. Course 1 (COURSE1)
  2. Course 2 (COURSE2)
  3. Course 3 (COURSE3)

🎉 All tests completed successfully!
🔗 Your Moodle API connection is working properly!
```

## Troubleshooting

### ❌ "Login failed" or "No token received"
- Check username/password are correct
- Verify service name exists in Moodle
- Ensure web services are enabled

### ❌ "Failed to get site info"
- Check if Moodle is running at the URL
- Verify the token has proper permissions
- Check network connectivity

### ❌ "Failed to get courses"
- Ensure the user has permission to view courses
- Check if courses exist in the Moodle instance

## Moodle Setup Requirements

Make sure your Moodle has:

1. **Web Services Enabled**
   - Site administration → Advanced features → Enable web services ✓

2. **REST Protocol Enabled**
   - Site administration → Plugins → Web services → Manage protocols → REST ✓

3. **External Service Created**
   - Site administration → Plugins → Web services → External services
   - Create service with required functions

4. **Token Generated**
   - Site administration → Plugins → Web services → Manage tokens
   - Create token for your user and service
