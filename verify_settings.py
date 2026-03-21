from playwright.sync_api import sync_playwright

def verify_settings(page):
    page.goto("http://localhost:3000/settings.php")
    page.wait_for_timeout(1000)

    # We shouldn't see two timezone selects anymore (duplicate card removed)
    # The timezone-select should exist exactly once.
    assert page.locator('#timezone-select').count() == 1

    # The inputs should now be properly associated with their labels
    # Clicking the label for "Use Ollama Cloud API" should toggle the checkbox
    cloud_mode_checkbox = page.locator('#ollama-cloud-mode')
    is_checked = cloud_mode_checkbox.is_checked()

    # Click the label text
    page.locator('label[for="ollama-cloud-mode"]').click()
    page.wait_for_timeout(500)

    # It should have toggled
    assert cloud_mode_checkbox.is_checked() != is_checked

    page.screenshot(path="/home/jules/verification/settings_page.png")
    page.wait_for_timeout(500)

if __name__ == "__main__":
    import os
    os.makedirs("/home/jules/verification/video", exist_ok=True)
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(record_video_dir="/home/jules/verification/video")
        page = context.new_page()
        try:
            verify_settings(page)
            print("Settings verification successful!")
        finally:
            context.close()
            browser.close()
